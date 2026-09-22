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

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Auto-load .env file if available in the project
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        if (getenv($k) === false && !isset($_ENV[$k])) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

// Serverless storage paths defaults for Vercel
$serverlessDefaults = [
    'LARAVEL_STORAGE_PATH' => '/tmp/storage',
    'VIEW_COMPILED_PATH'   => '/tmp/storage/framework/views',
    'APP_CONFIG_CACHE'     => '/tmp/bootstrap/cache/config.php',
    'APP_EVENTS_CACHE'     => '/tmp/bootstrap/cache/events.php',
    'APP_PACKAGES_CACHE'   => '/tmp/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE'     => '/tmp/bootstrap/cache/routes.php',
    'APP_SERVICES_CACHE'   => '/tmp/bootstrap/cache/services.php',
    'LOG_CHANNEL'          => 'stderr',
];

foreach ($serverlessDefaults as $key => $defaultVal) {
    if (getenv($key) === false && empty($_ENV[$key])) {
        putenv("{$key}={$defaultVal}");
        $_ENV[$key] = $defaultVal;
        $_SERVER[$key] = $defaultVal;
    }
}

// Adjust script name so Laravel router resolves correctly
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Ensure APP_KEY is always set
if (empty($_ENV['APP_KEY']) || trim($_ENV['APP_KEY']) === '') {
    $appKey = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? ($_SERVER['APP_KEY'] ?? 'base64:0JcCxZW+s3AKthTJcXnH1u/tP/LPMtoHoeB07s7d4zk='));
    putenv("APP_KEY={$appKey}");
    $_ENV['APP_KEY'] = $appKey;
    $_SERVER['APP_KEY'] = $appKey;
}

// Synchronize Database environment variables from environment (Vercel / .env / system)
$dbVars = [
    'DB_CONNECTION',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD',
    'MYSQL_ATTR_SSL_CA',
];

foreach ($dbVars as $var) {
    $val = getenv($var);
    if ($val === false || $val === null || $val === '') {
        $val = $_ENV[$var] ?? ($_SERVER[$var] ?? null);
    }
    if ($val !== null && $val !== '') {
        putenv("{$var}={$val}");
        $_ENV[$var] = $val;
        $_SERVER[$var] = $val;
    }
}

// Guarantee default DB_CONNECTION is mysql
if (empty($_ENV['DB_CONNECTION']) || trim((string)$_ENV['DB_CONNECTION']) === '') {
    putenv('DB_CONNECTION=mysql');
    $_ENV['DB_CONNECTION'] = 'mysql';
    $_SERVER['DB_CONNECTION'] = 'mysql';
}

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
try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Server Error (500):\n" . $e->getMessage() . "\n\nFile: " . $e->getFile() . ":" . $e->getLine() . "\n\nTrace:\n" . $e->getTraceAsString();
    exit;
}
