<?php
require_once "config/database.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Library Management System</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
        }

        .navbar {
            background: #1e3a5f;
            color: white;
            padding: 18px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 24px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
            font-size: 15px;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        .hero {
            min-height: 420px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(
                rgba(30, 58, 95, 0.90),
                rgba(30, 58, 95, 0.90)
            );
            color: white;
            padding: 40px 20px;
        }

        .hero-content {
            max-width: 800px;
        }

        .hero h2 {
            font-size: 46px;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 18px;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 13px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-primary {
            background: white;
            color: #1e3a5f;
        }

        .btn-secondary {
            border: 2px solid white;
            color: white;
        }

        .features {
            padding: 60px 8%;
        }

        .features h2 {
            text-align: center;
            margin-bottom: 40px;
            color: #1e3a5f;
        }

        .feature-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .feature-icon {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            margin-bottom: 12px;
            color: #1e3a5f;
        }

        .feature-card p {
            line-height: 1.6;
            color: #666;
        }

        footer {
            background: #172b44;
            color: white;
            text-align: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .hero h2 {
                font-size: 34px;
            }

            .feature-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar">

        <h1>📚 Smart Library</h1>

        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="auth/login.php">Login</a>
            <a href="auth/register.php">Register</a>
        </div>

    </nav>


    <!-- Hero Section -->
    <section class="hero">

        <div class="hero-content">

            <h2>Library Management System</h2>

            <p>
                A smart and modern library management system
                for managing books, members, issue and return
                transactions, fines and QR-code based book identification.
            </p>

            <div class="buttons">

                <a href="auth/login.php"
                   class="btn btn-primary">
                    Login
                </a>

                <a href="auth/register.php"
                   class="btn btn-secondary">
                    Register
                </a>

            </div>

        </div>

    </section>


    <!-- Features -->
    <section class="features">

        <h2>System Features</h2>

        <div class="feature-container">

            <div class="feature-card">

                <div class="feature-icon">📚</div>

                <h3>Book Management</h3>

                <p>
                    Add, update, delete and search books
                    available in the library.
                </p>

            </div>


            <div class="feature-card">

                <div class="feature-icon">🔄</div>

                <h3>Issue & Return</h3>

                <p>
                    Manage book issue and return transactions
                    with due dates and automatic fine calculation.
                </p>

            </div>


            <div class="feature-card">

                <div class="feature-icon">📱</div>

                <h3>QR Code</h3>

                <p>
                    Generate a unique QR code for every book
                    and use it to quickly identify books.
                </p>

            </div>


            <div class="feature-card">

                <div class="feature-icon">👨‍🎓</div>

                <h3>Member Management</h3>

                <p>
                    Manage student/member accounts and
                    their library activity.
                </p>

            </div>


            <div class="feature-card">

                <div class="feature-icon">🔍</div>

                <h3>Book Search</h3>

                <p>
                    Quickly search the library catalog by
                    title, author, ISBN or category.
                </p>

            </div>


            <div class="feature-card">

                <div class="feature-icon">📊</div>

                <h3>Dashboard</h3>

                <p>
                    View books, members, issued books,
                    returns and other library statistics.
                </p>

            </div>

        </div>

    </section>


    <!-- Footer -->
    <footer>

        <p>
            © <?php echo date("Y"); ?>
            Smart Library Management System
        </p>

    </footer>

</body>
</html>