<?php

// Ensure required serverless /tmp directories exist
$dirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/storage/app/public',
    '/tmp/bootstrap/cache',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Adjust script name so Laravel router resolves correctly
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Forward the request to Laravel's public entrypoint
require __DIR__ . '/../public/index.php';
