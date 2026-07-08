<?php

class WifiZoneLogger
{
    /**
     * Record an exception that is caught but intentionally not re-thrown
     * (best-effort operations such as notifications, cache access or probes).
     *
     * Uses error_log() only, so it never throws and never depends on the
     * database being reachable — safe to call from any catch block.
     */
    public static function note($context, Throwable $e)
    {
        error_log('[wifizone] ' . $context . ': ' . $e->getMessage());
    }

    public static function logPluginError($plugin, Throwable $e)
    {
        global $UPLOAD_PATH, $config;
        $line = date('Y-m-d H:i:s') . " [$plugin] " . WifiZoneSecurity::formatExceptionForLog($e) . "\n---\n";
        $file = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'plugin_errors.log';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);

        if (($config['wifizone_plugin_telegram_errors'] ?? 'no') === 'yes' && class_exists('Message')) {
            try {
                Message::sendTelegram("Plugin error [$plugin]: " . $e->getMessage());
            } catch (Throwable $ignored) {
            }
        }
    }

    public static function loadPlugins($pluginPath)
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        foreach (glob(File::pathFixer($pluginPath . DIRECTORY_SEPARATOR . '*.php')) as $filename) {
            $base = basename($filename);
            if ($base === 'wifizone.php') {
                continue;
            }
            try {
                include_once $filename;
            } catch (Throwable $e) {
                self::logPluginError($base, $e);
            }
        }
        $hub = $pluginPath . DIRECTORY_SEPARATOR . 'wifizone.php';
        if (file_exists($hub)) {
            include_once $hub;
        }
    }
}
