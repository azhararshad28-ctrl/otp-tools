<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e) {
            echo "<h2>ORIGINAL ERROR DETECTED BEFORE RENDER CRASH:</h2>";
            echo "<b>Message:</b> " . htmlspecialchars($e->getMessage()) . "<br><br>";
            echo "<b>File:</b> " . $e->getFile() . " (Line " . $e->getLine() . ")<br><br>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            exit;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => true,
        );
    })->create();

if (isset($_ENV['VERCEL_URL']) || isset($_SERVER['VERCEL_URL'])) {
    $app->useStoragePath('/tmp/storage');
}

return $app;
