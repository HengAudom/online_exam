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

// Ensure APP_KEY is always set
if (empty($_ENV['APP_KEY']) || trim($_ENV['APP_KEY']) === '') {
    $appKey = 'base64:0JcCxZW+s3AKthTJcXnH1u/tP/LPMtoHoeB07s7d4zk=';
    putenv("APP_KEY={$appKey}");
    $_ENV['APP_KEY'] = $appKey;
    $_SERVER['APP_KEY'] = $appKey;
}

// Always ensure database points to TiDB Cloud
putenv('DB_CONNECTION=mysql');
putenv('DB_HOST=gateway01.ap-southeast-1.prod.aws.tidbcloud.com');
putenv('DB_PORT=4000');
putenv('DB_DATABASE=online_exam_db');
putenv('DB_USERNAME=qGXpz3gtCzEhHAf.root');
putenv('DB_PASSWORD=5lO5eZJXXll22jGP');

$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_HOST'] = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$_ENV['DB_PORT'] = '4000';
$_ENV['DB_DATABASE'] = 'online_exam_db';
$_ENV['DB_USERNAME'] = 'qGXpz3gtCzEhHAf.root';
$_ENV['DB_PASSWORD'] = '5lO5eZJXXll22jGP';

$_SERVER['DB_CONNECTION'] = 'mysql';
$_SERVER['DB_HOST'] = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$_SERVER['DB_PORT'] = '4000';
$_SERVER['DB_DATABASE'] = 'online_exam_db';
$_SERVER['DB_USERNAME'] = 'qGXpz3gtCzEhHAf.root';
$_SERVER['DB_PASSWORD'] = '5lO5eZJXXll22jGP';

// Detect HTTPS behind reverse proxies like Vercel
if ((isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && (string)$_SERVER['HTTP_X_FORWARDED_PORT'] === '443')) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

// Ensure critical driver configs are never empty strings
if (empty($_ENV['APP_NAME']) || trim($_ENV['APP_NAME']) === '') {
    putenv('APP_NAME=OnlineExam');
    $_ENV['APP_NAME'] = 'OnlineExam';
    $_SERVER['APP_NAME'] = 'OnlineExam';
}

if (empty($_ENV['SESSION_DRIVER']) || trim($_ENV['SESSION_DRIVER']) === '') {
    putenv('SESSION_DRIVER=database');
    $_ENV['SESSION_DRIVER'] = 'database';
    $_SERVER['SESSION_DRIVER'] = 'database';
}

if (empty($_ENV['SESSION_LIFETIME']) || (int)$_ENV['SESSION_LIFETIME'] <= 0) {
    putenv('SESSION_LIFETIME=1440');
    $_ENV['SESSION_LIFETIME'] = '1440';
    $_SERVER['SESSION_LIFETIME'] = '1440';
}

if (empty($_ENV['SESSION_COOKIE']) || trim($_ENV['SESSION_COOKIE']) === '') {
    putenv('SESSION_COOKIE=online_exam_session');
    $_ENV['SESSION_COOKIE'] = 'online_exam_session';
    $_SERVER['SESSION_COOKIE'] = 'online_exam_session';
}

if (empty($_ENV['CACHE_STORE']) || trim($_ENV['CACHE_STORE']) === '') {
    putenv('CACHE_STORE=array');
    $_ENV['CACHE_STORE'] = 'array';
    $_SERVER['CACHE_STORE'] = 'array';
}

if (empty($_ENV['APP_MAINTENANCE_DRIVER']) || trim($_ENV['APP_MAINTENANCE_DRIVER']) === '') {
    putenv('APP_MAINTENANCE_DRIVER=file');
    $_ENV['APP_MAINTENANCE_DRIVER'] = 'file';
    $_SERVER['APP_MAINTENANCE_DRIVER'] = 'file';
}

// Forward the request to Laravel's public entrypoint
require __DIR__ . '/../public/index.php';
