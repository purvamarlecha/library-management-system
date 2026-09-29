<?php

session_start();

require_once "../config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Name validation
    if (empty($name)) {
        $error = "Please enter your full name.";
    }

    // Email validation
    elseif (empty($email)) {
        $error = "Please enter your email address.";
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }

    // Phone validation
    elseif (empty($phone)) {
        $error = "Please enter your phone number.";
    }

    elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error = "Phone number must contain exactly 10 digits.";
    }

    // Password validation
    elseif (empty($password)) {
        $error = "Please enter a password.";
    }

    elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    }

    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }

    else {

        // Check if email already exists
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = "An account with this email already exists.";

        } else {

            // Secure password hashing
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert new student
            $stmt = $conn->prepare(
                "INSERT INTO users
                (name, email, password, phone, role)
                VALUES (?, ?, ?, ?, 'student')"
            );

            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $hashed_password,
                $phone
            );

            if ($stmt->execute()) {

                $success = "Registration successful! You can now login.";

                // Clear form values
                $name = "";
                $email = "";
                $phone = "";

            } else {

                $error = "Registration failed. Please try again.";
            }
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | Smart Library</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
            background: #f8fafc;
            color: #0f172a;
        }

        .page {
            min-height: 100vh;
            display: flex;
        }

        /* LEFT SIDE */

        .left-panel {
            width: 48%;
            background: linear-gradient(
                135deg,
                #0f172a,
                #172554
            );
            color: white;
            padding: 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: "";
            position: absolute;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.18);
            top: -100px;
            right: -100px;
        }

        .left-panel::after {
            content: "";
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.12);
            bottom: -100px;
            left: -80px;
        }

        .brand {
            font-size: 25px;
            font-weight: 700;
            margin-bottom: 45px;
            position: relative;
            z-index: 1;
        }

        .brand span {
            color: #60a5fa;
        }

        .left-content {
            max-width: 520px;
            position: relative;
            z-index: 1;
        }

        .left-content h1 {
            font-size: 48px;
            line-height: 1.12;
            margin-bottom: 20px;
        }

        .left-content h1 span {
            color: #60a5fa;
        }

        .left-content p {
            color: #cbd5e1;
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 35px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 18px;
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .feature-text {
            color: #e2e8f0;
            font-size: 14px;
        }

        /* RIGHT SIDE */

        .right-panel {
            width: 52%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px;
        }

        .register-card {
            width: 100%;
            max-width: 510px;
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.10);
            border: 1px solid #e2e8f0;
        }

        .register-header {
            margin-bottom: 28px;
        }

        .register-header h2 {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .register-header p {
            color: #64748b;
            font-size: 14px;
        }

        /* ALERTS */

        .alert {
            padding: 13px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .success {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        /* FORM */

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #334155;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            height: 50px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 0 15px 0 44px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.10);
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            cursor: pointer;
            color: #64748b;
            font-size: 13px;
        }

        /* PASSWORD STRENGTH */

        .strength-container {
            margin-top: 8px;
        }

        .strength-bar {
            height: 5px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: 0.3s;
        }

        .strength-text {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }

        /* BUTTON */

        .register-btn {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 12px;
            background: #2563eb;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 5px;
        }

        .register-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .register-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .login-link {
            text-align: center;
            margin-top: 22px;
            color: #64748b;
            font-size: 14px;
        }

        .login-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* RESPONSIVE */

        @media (max-width: 900px) {

            .page {
                flex-direction: column;
            }

            .left-panel,
            .right-panel {
                width: 100%;
            }

            .left-panel {
                padding: 40px 25px;
            }

            .left-content h1 {
                font-size: 36px;
            }

            .right-panel {
                padding: 25px 18px;
            }

            .register-card {
                padding: 30px 22px;
            }
        }

    </style>

</head>

<body>

<div class="page">

    <!-- LEFT PANEL -->

    <div class="left-panel">

        <div class="brand">
            Smart<span>Library</span>
        </div>

        <div class="left-content">

            <h1>
                Join your
                <span>smart library.</span>
            </h1>

            <p>
                Create your library account and get access to
                books, borrowing history, QR-based book identification,
                and more.
            </p>

            <div class="feature">

                <div class="feature-icon">
                    📚
                </div>

                <div class="feature-text">
                    Browse and search available books
                </div>

            </div>

            <div class="feature">

                <div class="feature-icon">
                    📱
                </div>

                <div class="feature-text">
                    Identify books using QR codes
                </div>

            </div>

            <div class="feature">

                <div class="feature-icon">
                    🔒
                </div>

                <div class="feature-text">
                    Secure account and password protection
                </div>

            </div>

        </div>

    </div>


    <!-- RIGHT PANEL -->

    <div class="right-panel">

        <div class="register-card">

            <div class="register-header">

                <h2>Create Account</h2>

                <p>
                    Register as a student to access the library system.
                </p>

            </div>


            <?php if (!empty($error)): ?>

                <div class="alert error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <?php if (!empty($success)): ?>

                <div class="alert success">
                    <?php echo htmlspecialchars($success); ?>
                </div>

            <?php endif; ?>


            <form method="POST" id="registerForm">

                <!-- NAME -->

                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">👤</span>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            placeholder="Enter your full name"
                            value="<?php echo htmlspecialchars($name ?? ''); ?>"
                            required
                            maxlength="100"
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">✉️</span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($email ?? ''); ?>"
                            required
                            maxlength="100"
                        >

                    </div>

                </div>


                <!-- PHONE -->

                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">📱</span>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="form-control"
                            placeholder="10 digit phone number"
                            value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                            maxlength="10"
                            pattern="[0-9]{10}"
                            inputmode="numeric"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">🔒</span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Minimum 8 characters"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword('password', this)"
                        >
                            Show
                        </button>

                    </div>

                    <div class="strength-container">

                        <div class="strength-bar">
                            <div
                                class="strength-fill"
                                id="strengthFill"
                            ></div>
                        </div>

                        <div
                            class="strength-text"
                            id="strengthText"
                        >
                            Password strength
                        </div>

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">🔐</span>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Re-enter your password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword('confirm_password', this)"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <button
                    type="submit"
                    class="register-btn"
                    id="registerBtn"
                >
                    Create Account
                </button>

            </form>


            <div class="login-link">

                Already have an account?

                <a href="login.php">
                    Login here
                </a>

            </div>

        </div>

    </div>

</div>


<script>

    // Show / hide password
    function togglePassword(fieldId, button) {

        const field = document.getElementById(fieldId);

        if (field.type === "password") {

            field.type = "text";
            button.textContent = "Hide";

        } else {

            field.type = "password";
            button.textContent = "Show";
        }
    }


    // Password strength
    const password = document.getElementById("password");
    const strengthFill = document.getElementById("strengthFill");
    const strengthText = document.getElementById("strengthText");

    password.addEventListener("input", function () {

        const value = password.value;

        let strength = 0;

        if (value.length >= 8) {
            strength++;
        }

        if (/[A-Z]/.test(value)) {
            strength++;
        }

        if (/[0-9]/.test(value)) {
            strength++;
        }

        if (/[^A-Za-z0-9]/.test(value)) {
            strength++;
        }


        if (value.length === 0) {

            strengthFill.style.width = "0%";
            strengthText.textContent = "Password strength";

        } else if (strength <= 1) {

            strengthFill.style.width = "25%";
            strengthText.textContent = "Weak password";

        } else if (strength === 2) {

            strengthFill.style.width = "50%";
            strengthText.textContent = "Fair password";

        } else if (strength === 3) {

            strengthFill.style.width = "75%";
            strengthText.textContent = "Good password";

        } else {

            strengthFill.style.width = "100%";
            strengthText.textContent = "Strong password";
        }

    });


    // Phone number - allow digits only
    const phone = document.getElementById("phone");

    phone.addEventListener("input", function () {

        this.value = this.value.replace(/\D/g, "").slice(0, 10);

    });


    // Confirm password validation
    const form = document.getElementById("registerForm");

    form.addEventListener("submit", function (event) {

        const passwordValue =
            document.getElementById("password").value;

        const confirmValue =
            document.getElementById("confirm_password").value;

        if (passwordValue !== confirmValue) {

            event.preventDefault();

            alert("Passwords do not match.");

            return;
        }

        const button = document.getElementById("registerBtn");

        button.disabled = true;
        button.textContent = "Creating Account...";

    });

</script>

</body>
</html>