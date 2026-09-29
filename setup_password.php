<?php

require_once "config/database.php";

$adminPassword = password_hash("admin123", PASSWORD_DEFAULT);
$studentPassword = password_hash("student123", PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "UPDATE users 
     SET password = ? 
     WHERE email = ?"
);

// Update admin password
$adminEmail = "admin@library.com";
$stmt->bind_param("ss", $adminPassword, $adminEmail);
$stmt->execute();

// Update student password
$studentEmail = "student@library.com";
$stmt->bind_param("ss", $studentPassword, $studentEmail);
$stmt->execute();

$stmt->close();

echo "<h2>Password setup completed successfully!</h2>";
echo "<p>Admin login: admin@library.com / admin123</p>";
echo "<p>Student login: student@library.com / student123</p>";
echo "<p><strong>Important:</strong> Delete setup_passwords.php now.</p>";

?>