<?php

session_start();

require_once "../config/database.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Only admin can access this page
if ($_SESSION["user_role"] !== "admin") {
    header("Location: ../user/dashboard.php");
    exit();
}

// Get dashboard statistics

$totalBooks = 0;
$totalStudents = 0;
$totalIssued = 0;
$totalCategories = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM books");

if ($result) {
    $totalBooks = $result->fetch_assoc()["total"];
}

$result = $conn->query(
    "SELECT COUNT(*) AS total 
     FROM users 
     WHERE role = 'student'"
);

if ($result) {
    $totalStudents = $result->fetch_assoc()["total"];
}

$result = $conn->query(
    "SELECT COUNT(*) AS total 
     FROM issued_books 
     WHERE status = 'issued'"
);

if ($result) {
    $totalIssued = $result->fetch_assoc()["total"];
}

$result = $conn->query(
    "SELECT COUNT(*) AS total 
     FROM categories"
);

if ($result) {
    $totalCategories = $result->fetch_assoc()["total"];
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

    <title>Admin Dashboard | Smart Library</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #0f172a;
            color: white;
            padding: 25px 18px;
        }

        .brand {
            font-size: 23px;
            font-weight: 700;
            padding: 10px 12px 30px;
        }

        .brand span {
            color: #60a5fa;
        }

        .menu-title {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0 12px;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cbd5e1;
            text-decoration: none;
            padding: 13px 12px;
            border-radius: 10px;
            margin-bottom: 5px;
            font-size: 14px;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #1d4ed8;
            color: white;
        }

        .logout {
            position: absolute;
            bottom: 25px;
            left: 18px;
            right: 18px;
        }

        .logout a {
            background: rgba(255,255,255,0.07);
        }

        /* MAIN */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        /* TOPBAR */

        .topbar {
            height: 75px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 35px;
        }

        .topbar h1 {
            font-size: 21px;
        }

        .topbar p {
            color: #64748b;
            font-size: 13px;
            margin-top: 4px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dbeafe;
            color: #1d4ed8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .profile-info strong {
            display: block;
            font-size: 14px;
        }

        .profile-info span {
            color: #64748b;
            font-size: 12px;
        }

        /* CONTENT */

        .content {
            padding: 35px;
        }

        .welcome {
            margin-bottom: 28px;
        }

        .welcome h2 {
            font-size: 27px;
            margin-bottom: 7px;
        }

        .welcome p {
            color: #64748b;
            font-size: 14px;
        }

        /* STAT CARDS */

        .stats {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 22px;
            box-shadow:
                0 5px 20px rgba(15,23,42,0.04);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-card h3 {
            font-size: 28px;
            margin-top: 17px;
        }

        .stat-card p {
            color: #64748b;
            font-size: 13px;
            margin-top: 5px;
        }

        /* QUICK ACTIONS */

        .section-title {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .action-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 22px;
            text-decoration: none;
            color: #0f172a;
            transition: 0.2s;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow:
                0 10px 30px rgba(15,23,42,0.08);
            border-color: #bfdbfe;
        }

        .action-icon {
            font-size: 25px;
            margin-bottom: 12px;
        }

        .action-card h3 {
            font-size: 15px;
            margin-bottom: 6px;
        }

        .action-card p {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }

        /* MOBILE */

        @media (max-width: 1000px) {

            .stats {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .quick-actions {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {

            .sidebar {
                width: 70px;
                padding: 20px 10px;
            }

            .brand {
                font-size: 0;
                text-align: center;
            }

            .brand span {
                font-size: 20px;
            }

            .menu-title,
            .menu a span,
            .logout a span {
                display: none;
            }

            .menu a {
                justify-content: center;
                font-size: 20px;
            }

            .logout {
                left: 10px;
                right: 10px;
            }

            .main {
                margin-left: 70px;
            }

            .topbar {
                padding: 0 18px;
            }

            .profile-info {
                display: none;
            }

            .content {
                padding: 22px 18px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>

<body>

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="brand">
            Smart<span>Library</span>
        </div>

        <div class="menu-title">
            Main Menu
        </div>

        <nav class="menu">

            <a href="dashboard.php" class="active">
                📊
                <span>Dashboard</span>
            </a>

            <a href="books.php">
                📚
                <span>Books</span>
            </a>

            <a href="categories.php">
                🗂️
                <span>Categories</span>
            </a>

            <a href="authors.php">
                ✍️
                <span>Authors</span>
            </a>

            <a href="members.php">
                👥
                <span>Members</span>
            </a>

            <a href="issued_books.php">
                🔄
                <span>Issue / Return</span>
            </a>

            <a href="qr_books.php">
                📱
                <span>QR Books</span>
            </a>

        </nav>

        <div class="logout">

            <a href="../auth/logout.php">
                🚪
                <span>Logout</span>
            </a>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="main">

        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <h1>
                    Admin Dashboard
                </h1>

                <p>
                    Library Management System
                </p>

            </div>


            <div class="profile">

                <div class="avatar">
                    <?php
                    echo strtoupper(
                        substr($_SESSION["user_name"], 0, 1)
                    );
                    ?>
                </div>

                <div class="profile-info">

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["user_name"]
                        );
                        ?>
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>

        </header>


        <!-- CONTENT -->

        <section class="content">

            <div class="welcome">

                <h2>
                    Welcome back,
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["user_name"]
                    );
                    ?> 👋
                </h2>

                <p>
                    Here's what's happening in your library today.
                </p>

            </div>


            <!-- STATISTICS -->

            <div class="stats">

                <div class="stat-card">

                    <div class="stat-top">

                        <div>
                            📚
                        </div>

                        <div class="stat-icon">
                            📚
                        </div>

                    </div>

                    <h3>
                        <?php echo $totalBooks; ?>
                    </h3>

                    <p>
                        Total Books
                    </p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div>
                            👥
                        </div>

                        <div class="stat-icon">
                            👥
                        </div>

                    </div>

                    <h3>
                        <?php echo $totalStudents; ?>
                    </h3>

                    <p>
                        Registered Students
                    </p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div>
                            🔄
                        </div>

                        <div class="stat-icon">
                            🔄
                        </div>

                    </div>

                    <h3>
                        <?php echo $totalIssued; ?>
                    </h3>

                    <p>
                        Currently Issued
                    </p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div>
                            🗂️
                        </div>

                        <div class="stat-icon">
                            🗂️
                        </div>

                    </div>

                    <h3>
                        <?php echo $totalCategories; ?>
                    </h3>

                    <p>
                        Book Categories
                    </p>

                </div>

            </div>


            <!-- QUICK ACTIONS -->

            <h2 class="section-title">
                Quick Actions
            </h2>

            <div class="quick-actions">

                <a
                    href="books.php"
                    class="action-card"
                >

                    <div class="action-icon">
                        📚
                    </div>

                    <h3>
                        Manage Books
                    </h3>

                    <p>
                        Add, edit, delete and search
                        library books.
                    </p>

                </a>


                <a
                    href="issued_books.php"
                    class="action-card"
                >

                    <div class="action-icon">
                        🔄
                    </div>

                    <h3>
                        Issue / Return Books
                    </h3>

                    <p>
                        Manage book issuing,
                        returns and fines.
                    </p>

                </a>


                <a
                    href="qr_books.php"
                    class="action-card"
                >

                    <div class="action-icon">
                        📱
                    </div>

                    <h3>
                        QR Code Management
                    </h3>

                    <p>
                        Generate and manage
                        QR codes for books.
                    </p>

                </a>


                <a
                    href="members.php"
                    class="action-card"
                >

                    <div class="action-icon">
                        👥
                    </div>

                    <h3>
                        Manage Members
                    </h3>

                    <p>
                        View registered students
                        and their accounts.
                    </p>

                </a>


                <a
                    href="categories.php"
                    class="action-card"
                >

                    <div class="action-icon">
                        🗂️
                    </div>

                    <h3>
                        Categories
                    </h3>

                    <p>
                        Organize books by
                        category.
                    </p>

                </a>


                <a
                    href="authors.php"
                    class="action-card"
                >

                    <div class="action-icon">
                        ✍️
                    </div>

                    <h3>
                        Authors
                    </h3>

                    <p>
                        Manage book authors
                        and information.
                    </p>

                </a>

            </div>

        </section>

    </main>

</body>

</html>