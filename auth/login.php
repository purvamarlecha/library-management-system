<?php

session_start();

require_once "../config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $error = "Please enter both email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            /*
             * Passwords should be stored using password_hash().
             */

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                if ($user["role"] === "admin") {

                    header("Location: ../admin/dashboard.php");
                    exit();

                } else {

                    header("Location: ../user/dashboard.php");
                    exit();

                }

            } else {

                $error = "Incorrect email or password.";

            }

        } else {

            $error = "Incorrect email or password.";

        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login | Smart Library</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        :root {

            --primary: #2563eb;
            --primary-dark: #1d4ed8;

            --navy: #0f172a;
            --text: #172033;
            --muted: #64748b;

            --border: #e2e8f0;

            --background: #f1f5f9;
            --white: #ffffff;

            --danger: #dc2626;
            --danger-bg: #fef2f2;

        }


        body {

            min-height: 100vh;

            font-family:
                Inter,
                "Segoe UI",
                Arial,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fafc
                );

            color: var(--text);

        }


        /* =========================
           PAGE LAYOUT
        ========================== */

        .page {

            min-height: 100vh;

            display: grid;

            grid-template-columns:
                1fr 1fr;

        }


        /* =========================
           LEFT VISUAL SECTION
        ========================== */

        .visual-section {

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    145deg,
                    #0f172a,
                    #172554
                );

            color: white;

            padding: 55px;

            display: flex;

            flex-direction: column;

            justify-content: space-between;

        }


        .visual-section::before {

            content: "";

            position: absolute;

            width: 450px;

            height: 450px;

            border-radius: 50%;

            background: rgba(37, 99, 235, 0.18);

            top: -180px;

            right: -180px;

        }


        .visual-section::after {

            content: "";

            position: absolute;

            width: 300px;

            height: 300px;

            border-radius: 50%;

            background: rgba(96, 165, 250, 0.12);

            bottom: -130px;

            left: -100px;

        }


        .brand {

            position: relative;

            z-index: 2;

            display: flex;

            align-items: center;

            gap: 12px;

            font-size: 21px;

            font-weight: 700;

        }


        .brand-icon {

            width: 42px;

            height: 42px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: rgba(255,255,255,0.12);

            font-size: 21px;

            border: 1px solid rgba(255,255,255,0.15);

        }


        .visual-content {

            position: relative;

            z-index: 2;

            max-width: 520px;

        }


        .visual-content .badge {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 8px 13px;

            border-radius: 50px;

            background: rgba(255,255,255,0.10);

            border: 1px solid rgba(255,255,255,0.12);

            font-size: 13px;

            margin-bottom: 24px;

        }


        .dot {

            width: 7px;

            height: 7px;

            border-radius: 50%;

            background: #60a5fa;

        }


        .visual-content h1 {

            font-size: clamp(38px, 4vw, 58px);

            line-height: 1.08;

            letter-spacing: -1.5px;

            margin-bottom: 22px;

        }


        .visual-content h1 span {

            color: #60a5fa;

        }


        .visual-content p {

            color: #cbd5e1;

            font-size: 16px;

            line-height: 1.8;

            max-width: 480px;

        }


        .feature-list {

            margin-top: 35px;

            display: grid;

            gap: 14px;

        }


        .feature {

            display: flex;

            align-items: center;

            gap: 12px;

            color: #e2e8f0;

            font-size: 14px;

        }


        .feature-icon {

            width: 32px;

            height: 32px;

            border-radius: 9px;

            background: rgba(96,165,250,0.12);

            display: flex;

            align-items: center;

            justify-content: center;

        }


        .copyright {

            position: relative;

            z-index: 2;

            color: #94a3b8;

            font-size: 12px;

        }


        /* =========================
           LOGIN SECTION
        ========================== */

        .login-section {

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 40px;

        }


        .login-card {

            width: 100%;

            max-width: 470px;

            background: rgba(255,255,255,0.95);

            border: 1px solid rgba(226,232,240,0.9);

            border-radius: 24px;

            padding: 42px;

            box-shadow:
                0 25px 70px rgba(15,23,42,0.10);

        }


        .mobile-brand {

            display: none;

        }


        .login-heading {

            margin-bottom: 28px;

        }


        .login-heading h2 {

            font-size: 30px;

            margin-bottom: 8px;

            color: var(--navy);

        }


        .login-heading p {

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;

        }


        /* =========================
           ERROR
        ========================== */

        .error-message {

            display: flex;

            align-items: center;

            gap: 10px;

            background: var(--danger-bg);

            border: 1px solid #fecaca;

            color: var(--danger);

            padding: 12px 14px;

            border-radius: 11px;

            font-size: 13px;

            margin-bottom: 20px;

        }


        /* =========================
           FORM
        ========================== */

        .form-group {

            margin-bottom: 20px;

        }


        .form-label {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 8px;

            font-size: 13px;

            font-weight: 600;

            color: #334155;

        }


        .form-label a {

            color: var(--primary);

            text-decoration: none;

            font-size: 12px;

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

            font-size: 17px;

            pointer-events: none;

        }


        .form-input {

            width: 100%;

            height: 52px;

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 0 45px;

            outline: none;

            font-size: 14px;

            color: var(--text);

            background: #f8fafc;

            transition: 0.2s ease;

        }


        .form-input:hover {

            border-color: #cbd5e1;

        }


        .form-input:focus {

            border-color: var(--primary);

            background: white;

            box-shadow:
                0 0 0 4px rgba(37,99,235,0.10);

        }


        .password-toggle {

            position: absolute;

            right: 13px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            cursor: pointer;

            color: #64748b;

            font-size: 16px;

            padding: 5px;

        }


        /* =========================
           OPTIONS
        ========================== */

        .form-options {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

        }


        .remember {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #64748b;

            font-size: 13px;

            cursor: pointer;

        }


        .remember input {

            width: 16px;

            height: 16px;

            accent-color: var(--primary);

        }


        /* =========================
           LOGIN BUTTON
        ========================== */

        .login-button {

            width: 100%;

            height: 52px;

            border: none;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: white;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s ease;

            box-shadow:
                0 10px 20px rgba(37,99,235,0.20);

        }


        .login-button:hover {

            transform: translateY(-1px);

            box-shadow:
                0 14px 25px rgba(37,99,235,0.28);

        }


        .login-button:active {

            transform: translateY(0);

        }


        .login-button.loading {

            opacity: 0.75;

            cursor: wait;

        }


        /* =========================
           REGISTER
        ========================== */

        .register-text {

            text-align: center;

            margin-top: 25px;

            color: #64748b;

            font-size: 13px;

        }


        .register-text a {

            color: var(--primary);

            font-weight: 700;

            text-decoration: none;

        }


        .register-text a:hover {

            text-decoration: underline;

        }


        .home-link {

            display: block;

            text-align: center;

            margin-top: 18px;

            color: #94a3b8;

            text-decoration: none;

            font-size: 12px;

        }


        .home-link:hover {

            color: var(--primary);

        }


        /* =========================
           RESPONSIVE
        ========================== */

        @media (max-width: 900px) {

            .page {

                grid-template-columns: 1fr;

            }

            .visual-section {

                display: none;

            }

            .login-section {

                min-height: 100vh;

                padding: 25px;

            }

            .mobile-brand {

                display: flex;

                justify-content: center;

                align-items: center;

                gap: 10px;

                font-size: 20px;

                font-weight: 700;

                margin-bottom: 30px;

                color: var(--navy);

            }

        }


        @media (max-width: 500px) {

            .login-section {

                padding: 15px;

            }

            .login-card {

                padding: 28px 22px;

                border-radius: 18px;

            }

            .login-heading h2 {

                font-size: 26px;

            }

        }

    </style>

</head>


<body>


<div class="page">


    <!-- =========================
         LEFT SIDE
    ========================== -->

    <section class="visual-section">


        <div class="brand">

            <div class="brand-icon">
                📚
            </div>

            Smart Library

        </div>


        <div class="visual-content">

            <div class="badge">

                <span class="dot"></span>

                Smart Library Management

            </div>


            <h1>

                Your library,
                <span>smarter.</span>

            </h1>


            <p>

                Manage books, members, issue and return
                transactions — all from one modern
                library management platform.

            </p>


            <div class="feature-list">


                <div class="feature">

                    <div class="feature-icon">
                        📚
                    </div>

                    Smart Book Management

                </div>


                <div class="feature">

                    <div class="feature-icon">
                        📱
                    </div>

                    QR-Based Book Identification

                </div>


                <div class="feature">

                    <div class="feature-icon">
                        📊
                    </div>

                    Real-Time Library Dashboard

                </div>


            </div>

        </div>


        <div class="copyright">

            © <?php echo date("Y"); ?>
            Smart Library Management System

        </div>


    </section>



    <!-- =========================
         RIGHT SIDE
    ========================== -->

    <section class="login-section">


        <div class="login-card">


            <div class="mobile-brand">

                📚 Smart Library

            </div>


            <div class="login-heading">

                <h2>
                    Welcome back
                </h2>

                <p>
                    Sign in to continue to your library account.
                </p>

            </div>


            <?php if (!empty($error)): ?>

                <div class="error-message">

                    ⚠️

                    <span>
                        <?php echo htmlspecialchars($error); ?>
                    </span>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                id="loginForm"
            >


                <!-- EMAIL -->

                <div class="form-group">

                    <label
                        class="form-label"
                        for="email"
                    >

                        Email address

                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            ✉
                        </span>

                        <input

                            type="email"

                            id="email"

                            name="email"

                            class="form-input"

                            placeholder="you@example.com"

                            autocomplete="email"

                            required

                        >

                    </div>

                </div>



                <!-- PASSWORD -->

                <div class="form-group">

                    <div class="form-label">

                        <label for="password">
                            Password
                        </label>

                        <a href="#">
                            Forgot password?
                        </a>

                    </div>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            🔒
                        </span>


                        <input

                            type="password"

                            id="password"

                            name="password"

                            class="form-input"

                            placeholder="Enter your password"

                            autocomplete="current-password"

                            required

                        >


                        <button

                            type="button"

                            class="password-toggle"

                            id="togglePassword"

                            aria-label="Show password"

                        >
                            👁

                        </button>

                    </div>

                </div>



                <!-- OPTIONS -->

                <div class="form-options">


                    <label class="remember">

                        <input
                            type="checkbox"
                            name="remember"
                        >

                        Remember me

                    </label>


                </div>



                <!-- LOGIN -->

                <button

                    type="submit"

                    class="login-button"

                    id="loginButton"

                >

                    <span id="buttonText">
                        Sign in
                    </span>

                </button>


            </form>


            <div class="register-text">

                Don't have an account?

                <a href="register.php">
                    Create an account
                </a>

            </div>


            <a
                href="../index.php"
                class="home-link"
            >

                ← Back to homepage

            </a>


        </div>

    </section>


</div>



<script>

    /*
     * PASSWORD SHOW / HIDE
     */

    const password =
        document.getElementById("password");

    const togglePassword =
        document.getElementById("togglePassword");


    togglePassword.addEventListener(
        "click",
        function () {

            if (password.type === "password") {

                password.type = "text";

                togglePassword.textContent = "🙈";

                togglePassword.setAttribute(
                    "aria-label",
                    "Hide password"
                );

            } else {

                password.type = "password";

                togglePassword.textContent = "👁";

                togglePassword.setAttribute(
                    "aria-label",
                    "Show password"
                );

            }

        }
    );


    /*
     * LOGIN BUTTON LOADING STATE
     */

    const loginForm =
        document.getElementById("loginForm");

    const loginButton =
        document.getElementById("loginButton");

    const buttonText =
        document.getElementById("buttonText");


    loginForm.addEventListener(
        "submit",
        function () {

            loginButton.classList.add("loading");

            loginButton.disabled = true;

            buttonText.textContent =
                "Signing in...";

        }
    );


    /*
     * EMAIL VALIDATION
     */

    const email =
        document.getElementById("email");


    email.addEventListener(
        "blur",
        function () {

            if (
                email.value &&
                !email.checkValidity()
            ) {

                email.style.borderColor =
                    "#dc2626";

            } else {

                email.style.borderColor =
                    "";

            }

        }
    );

</script>


</body>

</html>