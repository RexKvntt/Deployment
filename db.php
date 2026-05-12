<?php

require_once __DIR__ . '/config.php';

function databaseConfig(): array
{
    $databaseUrl = envValue('DATABASE_URL', envValue('MYSQL_URL'));

    if ($databaseUrl) {
        $parts = parse_url($databaseUrl);
        if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
            throw new RuntimeException('DATABASE_URL is not a valid MySQL connection URL.');
        }

        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        return [
            'host' => $parts['host'],
            'port' => isset($parts['port']) ? (string) $parts['port'] : '3306',
            'name' => ltrim(rawurldecode($parts['path']), '/'),
            'user' => isset($parts['user']) ? rawurldecode($parts['user']) : '',
            'pass' => isset($parts['pass']) ? rawurldecode($parts['pass']) : '',
            'charset' => envValue('DB_CHARSET', 'utf8mb4'),
            'ssl_mode' => envValue('MYSQL_SSL_MODE', $query['ssl-mode'] ?? $query['sslmode'] ?? ''),
            'ssl_ca' => mysqlSslCaPath(),
        ];
    }

    return [
        'host' => envValue('DB_HOST', 'localhost'),
        'port' => envValue('DB_PORT', '3306'),
        'name' => envValue('DB_NAME', 'helios_db'),
        'user' => envValue('DB_USER', 'root'),
        'pass' => envValue('DB_PASS', ''),
        'charset' => envValue('DB_CHARSET', 'utf8mb4'),
        'ssl_mode' => envValue('MYSQL_SSL_MODE', ''),
        'ssl_ca' => mysqlSslCaPath(),
    ];
}

function mysqlSslCaPath(): ?string
{
    $path = envValue('MYSQL_SSL_CA');
    if ($path !== null) {
        return $path;
    }

    $base64 = envValue('AIVEN_CA_CERT_BASE64');
    if ($base64 !== null) {
        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            throw new RuntimeException('AIVEN_CA_CERT_BASE64 is not valid base64.');
        }

        $target = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'aiven-ca.pem';
        file_put_contents($target, $decoded);
        return $target;
    }

    $cert = envValue('AIVEN_CA_CERT');
    if ($cert !== null) {
        $target = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'aiven-ca.pem';
        file_put_contents($target, str_replace('\n', "\n", $cert));
        return $target;
    }

    return null;
}

$db = databaseConfig();
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $db['host'],
    $db['port'],
    $db['name'],
    $db['charset']
);

$sslMode = strtolower((string) $db['ssl_mode']);
if ($sslMode !== '') {
    $dsn .= ';sslmode=' . $sslMode;
}

if (!empty($db['ssl_ca'])) {
    $dsn .= ';sslrootcert=' . $db['ssl_ca'];
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

if (!empty($db['ssl_ca']) && defined('PDO::MYSQL_ATTR_SSL_CA')) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $db['ssl_ca'];
}

if (in_array($sslMode, ['verify-ca', 'verify-full'], true) && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection error. Please try again later.');
}
