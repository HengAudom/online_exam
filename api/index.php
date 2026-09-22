<?php

@header_remove('X-Powered-By');
@ini_set('expose_php', 'off');

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (str_contains($requestUri, 'manifest.json') && !str_contains($requestUri, 'manifest.webmanifest')) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Not Found']);
    exit;
}

// Ensure required serverless /tmp directories exist
$dirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/storage/app/public',
    '/tmp/bootstrap/cache',
];

if (!is_dir('/tmp/storage/framework/sessions')) {
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
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

// Database credentials fallback (allow Vercel Environment Variables to take priority)
$dbConn     = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'mysql');
$dbHost     = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com');
$dbPort     = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '4000');
$dbDatabase = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? 'online_exam_db');
$dbUsername = getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? 'qGXpz3gtCzEhHAf.root');
$dbPassword = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '5lO5eZJXXll22jGP');

putenv("DB_CONNECTION={$dbConn}");
putenv("DB_HOST={$dbHost}");
putenv("DB_PORT={$dbPort}");
putenv("DB_DATABASE={$dbDatabase}");
putenv("DB_USERNAME={$dbUsername}");
putenv("DB_PASSWORD={$dbPassword}");

$_ENV['DB_CONNECTION'] = $dbConn;
$_ENV['DB_HOST']       = $dbHost;
$_ENV['DB_PORT']       = $dbPort;
$_ENV['DB_DATABASE']   = $dbDatabase;
$_ENV['DB_USERNAME']   = $dbUsername;
$_ENV['DB_PASSWORD']   = $dbPassword;

$_SERVER['DB_CONNECTION'] = $dbConn;
$_SERVER['DB_HOST']       = $dbHost;
$_SERVER['DB_PORT']       = $dbPort;
$_SERVER['DB_DATABASE']   = $dbDatabase;
$_SERVER['DB_USERNAME']   = $dbUsername;
$_SERVER['DB_PASSWORD']   = $dbPassword;

// Detect HTTPS behind reverse proxies like Vercel
if ((isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && (string)$_SERVER['HTTP_X_FORWARDED_PORT'] === '443')) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

// Ensure critical driver configs are never empty strings
if (empty($_ENV['APP_URL']) || trim($_ENV['APP_URL']) === '' || str_contains($_ENV['APP_URL'], 'onlin-exam.vercel.app')) {
    putenv('APP_URL=https://onlinexam.site');
    $_ENV['APP_URL'] = 'https://onlinexam.site';
    $_SERVER['APP_URL'] = 'https://onlinexam.site';
}

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

putenv('CACHE_STORE=database');
$_ENV['CACHE_STORE'] = 'database';
$_SERVER['CACHE_STORE'] = 'database';

if (empty($_ENV['APP_MAINTENANCE_DRIVER']) || trim($_ENV['APP_MAINTENANCE_DRIVER']) === '') {
    putenv('APP_MAINTENANCE_DRIVER=file');
    $_ENV['APP_MAINTENANCE_DRIVER'] = 'file';
    $_SERVER['APP_MAINTENANCE_DRIVER'] = 'file';
}

// Forward the request to Laravel's public entrypoint
require __DIR__ . '/../public/index.php';
