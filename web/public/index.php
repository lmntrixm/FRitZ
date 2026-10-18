<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// The app lives next to the web root. Locally that is ../ (this file sits in web/public);
// on DirectAdmin it is ../fritz-app, because only public_html is served and there is no SSH for symlinks.
$base = is_file(__DIR__.'/../fritz-app/bootstrap/app.php') ? __DIR__.'/../fritz-app' : __DIR__.'/..';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $base.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $base.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
