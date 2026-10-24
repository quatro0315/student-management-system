<?php
// Production-ready configuration for Render + Aiven MySQL.
// Configure these values in Render's Environment settings; never commit secrets.

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function env_value(string $key, ?string $default = null): ?string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

$dbHost = env_value('DB_HOST', env_value('MYSQLHOST', '127.0.0.1'));
$dbPort = env_value('DB_PORT', env_value('MYSQLPORT', '3306'));
$dbName = env_value('DB_NAME', env_value('MYSQLDATABASE', 'student_db'));
$dbUser = env_value('DB_USER', env_value('MYSQLUSER', 'root'));
$dbPass = env_value('DB_PASS', env_value('MYSQLPASSWORD', ''));
$dbSslCa = env_value('DB_SSL_CA');

define('ALLOW_ROLE_SELECTION_ON_SIGNUP', filter_var(
    env_value('ALLOW_ROLE_SELECTION_ON_SIGNUP', 'false'),
    FILTER_VALIDATE_BOOLEAN
));

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 10,
];

if ($dbSslCa && is_readable($dbSslCa)) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $dbSslCa;
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }
}

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to connect to the database. Verify the Render environment variables and Aiven connection settings.');
}
