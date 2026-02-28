<?php
/**
 * Root index.php Bridge for Hostinger
 * This file sits at the project root (= public_html on Hostinger)
 * and bootstraps Laravel from here.
 */
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$basePath = __DIR__;

// Maintenance mode
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register Composer autoloader
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request
(require_once $basePath.'/bootstrap/app.php')->handleRequest(Request::capture());
