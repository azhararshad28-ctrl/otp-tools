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

try {
    require __DIR__ . "/../public/index.php";
} catch (\Throwable $e) {
    http_response_code(500);
    echo "<div style='font-family:sans-serif; padding: 20px; background: #fff3f3; border: 1px solid red; border-radius: 5px;'>";
    echo "<h2 style='color:red;'>🚨 REAL ERROR DETECTED:</h2>";
    echo "<b>Message:</b> " . htmlspecialchars($e->getMessage()) . "<br><br>";
    echo "<b>File:</b> " . $e->getFile() . " (Line " . $e->getLine() . ")<br><br>";
    echo "<h3>Stack Trace:</h3><pre style='background: #333; color: #fff; padding: 15px; overflow: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
