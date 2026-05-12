<?php

function envValue(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function appUrl(string $path = ''): string
{
    $baseUrl = rtrim(envValue('APP_URL', ''), '/');

    if ($baseUrl === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host;
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

function smtpConfig(): array
{
    return [
        'host' => envValue('SMTP_HOST', 'smtp.gmail.com'),
        'port' => (int) envValue('SMTP_PORT', '587'),
        'username' => envValue('SMTP_USERNAME', ''),
        'password' => envValue('SMTP_PASSWORD', ''),
        'from_email' => envValue('SMTP_FROM_EMAIL', envValue('SMTP_USERNAME', '')),
        'from_name' => envValue('SMTP_FROM_NAME', envValue('APP_NAME', 'Helios University Academic Hub')),
    ];
}

function requireEnv(string $key): string
{
    $value = envValue($key);
    if ($value === null) {
        throw new RuntimeException($key . ' environment variable is required.');
    }
    return $value;
}
