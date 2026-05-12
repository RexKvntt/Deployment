<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_helpers.php';

$firstName = envValue('ADMIN_FIRSTNAME', 'System');
$lastName = envValue('ADMIN_LASTNAME', 'Administrator');
$email = envValue('ADMIN_EMAIL', '');
$phone = envValue('ADMIN_PHONE', '');
$username = envValue('ADMIN_USERNAME', '26-0001');
$password = envValue('ADMIN_PASSWORD', '');

if ($email === '' || $phone === '' || $password === '') {
    fwrite(STDERR, "Set ADMIN_EMAIL, ADMIN_PHONE, and ADMIN_PASSWORD before running this script.\n");
    exit(1);
}

$existing = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$existing->execute([$username]);
if ($existing->fetch()) {
    fwrite(STDOUT, "Admin user {$username} already exists. Nothing changed.\n");
    exit(0);
}

$requestId = 'ADMIN-' . strtoupper(bin2hex(random_bytes(4)));
$authId = 'AUTH-' . strtoupper(bin2hex(random_bytes(4)));
$fullName = trim($firstName . ' ' . $lastName);
$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("
        INSERT INTO authorized_people
            (id, firstname, lastname, email, phonenumber, role, status, created_at, activated_at)
        VALUES
            (:id, :firstname, :lastname, :email, :phone, 'admin', 'activated', NOW(), NOW())
    ");
    $stmt->execute([
        ':id' => $authId,
        ':firstname' => $firstName,
        ':lastname' => $lastName,
        ':email' => $email,
        ':phone' => normalizePhone($phone),
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO users
            (request_id, authorized_person_id, firstname, lastname, fullname, phonenumber, username, email, password, role, status, activation_request, registered_at, activated_at, must_change_password)
        VALUES
            (:request_id, :authorized_person_id, :firstname, :lastname, :fullname, :phone, :username, :email, :password, 'admin', 'active', 0, NOW(), NOW(), 0)
    ");
    $stmt->execute([
        ':request_id' => $requestId,
        ':authorized_person_id' => $authId,
        ':firstname' => $firstName,
        ':lastname' => $lastName,
        ':fullname' => $fullName,
        ':phone' => encryptData($phone),
        ':username' => $username,
        ':email' => encryptData($email),
        ':password' => $passwordHash,
    ]);

    $userId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO admin_accounts (user_id, created_at) VALUES (:user_id, NOW())");
    $stmt->execute([':user_id' => $userId]);

    $pdo->commit();
    fwrite(STDOUT, "Admin user {$username} created.\n");
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Admin creation failed: {$e->getMessage()}\n");
    exit(1);
}
