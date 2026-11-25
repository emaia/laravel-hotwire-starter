<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$basePath = dirname(__DIR__, 2);
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($requestPath !== '/' && is_file($basePath.'/public'.$requestPath)) {
    return false;
}

require $basePath.'/vendor/autoload.php';
$app = require $basePath.'/bootstrap/app.php';
$request = Request::capture();
$app->instance('request', $request);
$app->make(Kernel::class)->bootstrap();

// Keep fixture routes unavailable unless a disposable SQLite environment is explicit.
$database = getenv('BROWSER_TEST_DATABASE');
if (! $app->environment('testing') || ! is_string($database)
    || ! str_starts_with($database, sys_get_temp_dir().'/')
    || config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $database) {
    http_response_code(404);

    return;
}

View::addNamespace('fixtures', __DIR__);
Route::get('/_test/listings', require __DIR__.'/listing-route.php')->middleware('web');
$app->handleRequest($request);
