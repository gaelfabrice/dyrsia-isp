<?php

class WifiZoneApi
{
    public static function issueJwt($adminId, $ttl = 86400)
    {
        $secret = WifiZoneCore::config('wifizone_jwt_secret', 'change-me');
        $payload = [
            'sub' => (int) $adminId,
            'iat' => time(),
            'exp' => time() + $ttl,
        ];
        $body = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])) . '.' .
            base64_encode(json_encode($payload));
        $sig = hash_hmac('sha256', $body, $secret);
        return $body . '.' . $sig;
    }

    public static function verifyJwt($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        $secret = WifiZoneCore::config('wifizone_jwt_secret', 'change-me');
        $expected = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $secret);
        if (!hash_equals($expected, $parts[2])) {
            return null;
        }
        $payload = json_decode(base64_decode($parts[1]), true);
        if (!$payload || ($payload['exp'] ?? 0) < time()) {
            return null;
        }
        return $payload;
    }

    public static function authenticateRequest()
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            $payload = self::verifyJwt(trim($m[1]));
            if ($payload) {
                $_SESSION['aid'] = $payload['sub'];
                return Admin::_info();
            }
        }
        return null;
    }

    public static function jsonResponse($success, $message = '', $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit;
    }
}
