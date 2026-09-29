<?php

require_once "config/database.php";

$email = "admin@library.com";
$password = "admin123";

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "UPDATE users
     SET password = ?
     WHERE email = ?"
);

$stmt->bind_param(
    "ss",
    $hashedPassword,
    $email
);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {
        echo "<h2>Admin password reset successfully!</h2>";
    } else {
        echo "<h2>Admin account found, but no change was needed.</h2>";
    }

    echo "<p>Email: admin@library.com</p>";
    echo "<p>Password: admin123</p>";
    echo "<p><strong>Delete reset_admin.php after this.</strong></p>";

} else {
    echo "Error: " . htmlspecialchars($stmt->error);
}

$stmt->close();

?>