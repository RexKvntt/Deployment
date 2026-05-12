<?php
/**
 * One-time fix: sets the admin's encrypted email and phone in the DB.
 * DELETE THIS FILE after running it!
 */
require_once 'db.php';
require_once 'cryptograph_process.php';

// ── SET THESE VALUES ──────────────────────────────────────────
$adminUsername = envValue('ADMIN_FIX_USERNAME', '');
$adminEmail    = envValue('ADMIN_FIX_EMAIL', '');
$adminPhone    = envValue('ADMIN_FIX_PHONE', '');
// ─────────────────────────────────────────────────────────────
if (envValue('APP_ENV') === 'production') {
    http_response_code(403);
    exit('This maintenance script is disabled in production.');
}

if ($adminUsername === '' || $adminEmail === '' || $adminPhone === '') {
    exit('Set ADMIN_FIX_USERNAME, ADMIN_FIX_EMAIL, and ADMIN_FIX_PHONE before running this script.');
}

$stmt = $pdo->prepare("
    UPDATE users
       SET email       = :email,
           phonenumber = :phone
     WHERE username    = :username
");
$stmt->execute([
    ':email'    => encryptData($adminEmail),
    ':phone'    => encryptData($adminPhone),
    ':username' => $adminUsername,
]);

echo "Done! Email and phone updated for user: $adminUsername<br>";
echo "Encrypted email saved: " . encryptData($adminEmail) . "<br>";
echo "<strong style='color:red'>DELETE this file now!</strong>";
