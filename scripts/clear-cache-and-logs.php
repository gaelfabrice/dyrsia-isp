<?php
/**
 * Vide le cache fichiers (system/cache, ui/cache, ui/compiled) et tbl_logs.
 *
 * Usage: php scripts/clear-cache-and-logs.php [--logs-only] [--cache-only]
 */

$root = dirname(__DIR__);
$logsOnly = in_array('--logs-only', $argv ?? [], true);
$cacheOnly = in_array('--cache-only', $argv ?? [], true);

function clear_file_cache(string $root): array
{
    $cleared = 0;
    $dirs = [
        $root . '/system/cache',
        $root . '/ui/cache',
        $root . '/ui/compiled',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getFilename() !== 'index.html') {
                if (@unlink($item->getPathname())) {
                    $cleared++;
                }
            }
        }
    }

    return ['files' => $cleared];
}

if (!$logsOnly) {
    $result = clear_file_cache($root);
    echo "Cache: {$result['files']} fichier(s) supprimé(s).\n";
}

if (!$cacheOnly) {
    require $root . '/init.php';
    $before = (int) ORM::for_table('tbl_logs')->count();
    ORM::raw_execute('DELETE FROM tbl_logs');
    $after = (int) ORM::for_table('tbl_logs')->count();
    echo "Activity log: {$before} entrée(s) supprimée(s) (reste: {$after}).\n";
}
