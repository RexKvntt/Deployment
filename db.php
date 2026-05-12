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

        return [
            'host' => $parts['host'],
            'port' => isset($parts['port']) ? (string) $parts['port'] : '3306',
            'name' => ltrim(rawurldecode($parts['path']), '/'),
            'user' => isset($parts['user']) ? rawurldecode($parts['user']) : '',
            'pass' => isset($parts['pass']) ? rawurldecode($parts['pass']) : '',
            'charset' => envValue('DB_CHARSET', 'utf8mb4'),
        ];
    }

    return [
        'host' => envValue('DB_HOST', 'localhost'),
        'port' => envValue('DB_PORT', '3306'),
        'name' => envValue('DB_NAME', 'helios_db'),
        'user' => envValue('DB_USER', 'root'),
        'pass' => envValue('DB_PASS', ''),
        'charset' => envValue('DB_CHARSET', 'utf8mb4'),
    ];
}

$db = databaseConfig();
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $db['host'],
    $db['port'],
    $db['name'],
    $db['charset']
);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection error. Please try again later.');
}
