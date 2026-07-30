<?php

/**
 * TOTP (Google Authenticator / RFC 6238) pour les comptes SuperAdmin.
 */
class SuperAdminTotp
{
    private const SESSION_KEY = 'sa_2fa';
    private const SESSION_SECRET = 'sa_2fa_secret';
    private const PENDING_TTL = 600;
    private const ISSUER = 'DYRSIA WifiZone';

    public static function ensureSchema(): void
    {
        try {
            ORM::raw_execute(
                "CREATE TABLE IF NOT EXISTS wifizone_admin_totp (
                    admin_id INT UNSIGNED NOT NULL PRIMARY KEY,
                    secret_enc TEXT NOT NULL,
                    enabled_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (Throwable $e) {
            error_log('SuperAdminTotp::ensureSchema: ' . $e->getMessage());
        }
    }

    public static function requiresTotp(array $userRow): bool
    {
        return ($userRow['user_type'] ?? '') === 'SuperAdmin';
    }

    public static function isEnabled(int $adminId): bool
    {
        if ($adminId <= 0) {
            return false;
        }
        try {
            return ORM::for_table('wifizone_admin_totp')->where('admin_id', $adminId)->find_one() !== false;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function beginPending(int $adminId, string $mode): void
    {
        $_SESSION[self::SESSION_KEY] = [
            'user_id' => $adminId,
            'exp' => time() + self::PENDING_TTL,
            'mode' => $mode === 'setup' ? 'setup' : 'verify',
        ];
        if ($mode === 'setup') {
            $_SESSION[self::SESSION_SECRET] = self::generateSecret();
        } else {
            unset($_SESSION[self::SESSION_SECRET]);
        }
    }

    public static function clearPending(): void
    {
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::SESSION_SECRET]);
    }

    /** @return array{user_id:int,mode:string}|null */
    public static function pending(): ?array
    {
        $p = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($p) || empty($p['user_id'])) {
            return null;
        }
        if ((int) ($p['exp'] ?? 0) < time()) {
            self::clearPending();

            return null;
        }

        return [
            'user_id' => (int) $p['user_id'],
            'mode' => ($p['mode'] ?? '') === 'setup' ? 'setup' : 'verify',
        ];
    }

    public static function pendingSetupSecret(): string
    {
        return (string) ($_SESSION[self::SESSION_SECRET] ?? '');
    }

    public static function provisioningUri(string $secret, string $accountName): string
    {
        global $config;
        $issuer = trim((string) ($config['CompanyName'] ?? self::ISSUER));
        if ($issuer === '') {
            $issuer = self::ISSUER;
        }
        $label = rawurlencode($issuer . ':' . $accountName);
        $issuerEnc = rawurlencode($issuer);

        return 'otpauth://totp/' . $label . '?secret=' . $secret . '&issuer=' . $issuerEnc . '&digits=6&period=30';
    }

    public static function qrImageUrl(string $otpauthUri): string
    {
        if (function_exists('getUrl')) {
            return getUrl('admin/2fa-qr');
        }

        return APP_URL . '/?_route=admin/2fa-qr';
    }

    /** PNG otpauth (session 2FA setup en cours requise). */
    public static function sendQrPng(string $otpauthUri): void
    {
        if (headers_sent()) {
            return;
        }
        $root = dirname(__DIR__, 2);
        $qrLib = $root . DIRECTORY_SEPARATOR . 'qrcode' . DIRECTORY_SEPARATOR . 'qrlib.php';
        if (!is_file($qrLib)) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'QR library missing';
            exit;
        }
        require_once $qrLib;
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        QRcode::png($otpauthUri, false, QR_ECLEVEL_M, 6, 2);
        exit;
    }

    public static function ensureSetupSecret(): string
    {
        $secret = self::pendingSetupSecret();
        if ($secret !== '') {
            return $secret;
        }
        $_SESSION[self::SESSION_SECRET] = self::generateSecret();

        return (string) $_SESSION[self::SESSION_SECRET];
    }

    public static function touchPending(): void
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            return;
        }
        $_SESSION[self::SESSION_KEY]['exp'] = time() + self::PENDING_TTL;
    }

    public static function verifyCodeForAdmin(int $adminId, string $code): bool
    {
        $secret = self::loadSecretPlain($adminId);
        if ($secret === '') {
            return false;
        }

        return self::verifyCode($secret, $code);
    }

    public static function verifySetupCode(string $code): bool
    {
        $secret = self::pendingSetupSecret();
        if ($secret === '') {
            return false;
        }

        return self::verifyCode($secret, $code);
    }

    public static function enableForAdmin(int $adminId, string $secretPlain): void
    {
        $enc = self::encryptSecret($secretPlain);
        $now = date('Y-m-d H:i:s');
        $row = ORM::for_table('wifizone_admin_totp')->where('admin_id', $adminId)->find_one();
        if (!$row) {
            $row = ORM::for_table('wifizone_admin_totp')->create();
            $row->admin_id = $adminId;
            $row->enabled_at = $now;
        }
        $row->secret_enc = $enc;
        $row->updated_at = $now;
        $row->save();
    }

    public static function disableForAdmin(int $adminId): void
    {
        ORM::for_table('wifizone_admin_totp')->where('admin_id', $adminId)->delete_many();
    }

    public static function enforceVerifyRateLimit(int $adminId): bool
    {
        $id = WifiZoneSecurity::clientIp() . ':sa_totp:' . $adminId;

        return WifiZoneSecurity::rateLimit('superadmin_totp_verify', $id, 8, 900);
    }

    public static function finalizeLogin($d, string $username): void
    {
        global $isApi;
        $adminId = (int) $d['id'];
        if ($adminId <= 0) {
            _alert(Lang::T('Invalid Username or Password') . '.', 'danger', 'admin');
        }
        $_SESSION['aid'] = $adminId;
        $_SESSION['user_type'] = $d['user_type'];
        $_SESSION['sa_2fa_verified'] = time();
        DemoShowcase::onLogin($adminId);
        try {
            $token = Admin::setCookie($adminId);
            $d->last_login = date('Y-m-d H:i:s');
            $d->save();
            _log($username . ' ' . Lang::T('Login Successful'), $d['user_type'], $adminId);
        } catch (Throwable $e) {
            error_log('wifizone admin login post-save: ' . $e->getMessage());
            $token = '';
        }
        if ($isApi) {
            if ($token) {
                showResult(true, Lang::T('Login Successful'), ['token' => 'a.' . $token]);
            } else {
                showResult(false, Lang::T('Invalid Username or Password'));
            }
        }
        if (!empty($_SESSION['tenant_slug'])) {
            _alert(Lang::T('Login Successful'), 'success', 'dashboard_tenant=' . $_SESSION['tenant_slug']);
        } else {
            _alert(Lang::T('Login Successful'), 'success', 'dashboard');
        }
    }

    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function verifyCode(string $secretBase32, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', trim($code));
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $secret = self::base32Decode($secretBase32);
        if ($secret === '') {
            return false;
        }
        $timeSlice = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::getCode($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    private static function getCode(string $secretKey, int $timeSlice): string
    {
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord($hash[19]) & 0x0f;
        $value = (
            ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }

    private static function loadSecretPlain(int $adminId): string
    {
        try {
            $row = ORM::for_table('wifizone_admin_totp')->where('admin_id', $adminId)->find_one();
            if (!$row) {
                return '';
            }

            return self::decryptSecret((string) $row->secret_enc);
        } catch (Throwable $e) {
            return '';
        }
    }

    private static function encryptSecret(string $plain): string
    {
        $key = hash('sha256', WifiZoneSecurity::appKey() ?: WifiZoneSecurity::legacySecret(), true);
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new RuntimeException('TOTP encrypt failed');
        }

        return base64_encode($iv . $cipher);
    }

    private static function decryptSecret(string $payload): string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $key = hash('sha256', WifiZoneSecurity::appKey() ?: WifiZoneSecurity::legacySecret(), true);
        $plain = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return $plain === false ? '' : $plain;
    }

    private static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $chunks = str_split($binary, 5);
        $encoded = '';
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $encoded .= $alphabet[bindec($chunk)];
        }

        return $encoded;
    }

    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
        if ($b32 === '') {
            return '';
        }
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($b32) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                return '';
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = str_split($binary, 8);
        $out = '';
        foreach ($bytes as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
