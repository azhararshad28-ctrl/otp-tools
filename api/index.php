<?php
if (isset($_ENV['VERCEL_URL']) || isset($_SERVER['VERCEL_URL'])) {
    $storage = '/tmp/storage';
    if (!is_dir($storage.'/framework/views')) mkdir($storage.'/framework/views', 0777, true);
    if (!is_dir($storage.'/framework/cache/data')) mkdir($storage.'/framework/cache/data', 0777, true);
    if (!is_dir($storage.'/framework/sessions')) mkdir($storage.'/framework/sessions', 0777, true);
    if (!is_dir($storage.'/logs')) mkdir($storage.'/logs', 0777, true);
    
    putenv('VIEW_COMPILED_PATH=' . $storage.'/framework/views');
    $_ENV['VIEW_COMPILED_PATH'] = $storage.'/framework/views';
    $_SERVER['VIEW_COMPILED_PATH'] = $storage.'/framework/views';
}

require __DIR__ . "/../public/index.php";
