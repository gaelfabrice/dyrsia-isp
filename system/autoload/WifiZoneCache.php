<?php

class WifiZoneCache
{
    public static function get($key, $ttl = 300)
    {
        if (WifiZoneCore::config('wifizone_redis_enabled', 'no') === 'yes' && class_exists('Redis')) {
            try {
                $r = self::redis();
                if ($r) {
                    $v = $r->get('wz:' . $key);
                    return $v !== false ? json_decode($v, true) : null;
                }
            } catch (Throwable $e) {
                WifiZoneLogger::note('redis cache get, falling back to file cache', $e);
            }
        }
        global $CACHE_PATH;
        $file = $CACHE_PATH . DIRECTORY_SEPARATOR . 'wz_' . md5($key) . '.json';
        if (!file_exists($file)) {
            return null;
        }
        $data = json_decode(file_get_contents($file), true);
        if (!$data || ($data['exp'] ?? 0) < time()) {
            @unlink($file);
            return null;
        }
        return $data['val'] ?? null;
    }

    public static function set($key, $value, $ttl = 300)
    {
        if (WifiZoneCore::config('wifizone_redis_enabled', 'no') === 'yes' && class_exists('Redis')) {
            try {
                $r = self::redis();
                if ($r) {
                    $r->setex('wz:' . $key, $ttl, json_encode($value));
                    return;
                }
            } catch (Throwable $e) {
                WifiZoneLogger::note('redis cache set, falling back to file cache', $e);
            }
        }
        global $CACHE_PATH;
        $file = $CACHE_PATH . DIRECTORY_SEPARATOR . 'wz_' . md5($key) . '.json';
        file_put_contents($file, json_encode(['exp' => time() + $ttl, 'val' => $value]), LOCK_EX);
    }

    private static function redis()
    {
        static $client = null;
        if ($client !== null) {
            return $client;
        }
        $client = new Redis();
        $host = WifiZoneCore::config('wifizone_redis_host', '127.0.0.1');
        $port = (int) WifiZoneCore::config('wifizone_redis_port', 6379);
        if (@$client->connect($host, $port, 1)) {
            return $client;
        }
        $client = false;
        return false;
    }
}
