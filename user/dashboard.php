<?php

session_start();
require_once "../config/database.php";

/* =====================================================
   STUDENT ACCESS PROTECTION
===================================================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION["user_role"] !== "student") {
    header("Location: ../admin/dashboard.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

$user_name = $_SESSION["user_name"] ?? "Student";
$user_email = $_SESSION["user_email"] ?? "";


/* =====================================================
   DASHBOARD STATISTICS
===================================================== */

// Total book titles
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM books"
);

$total_books = (int) $result->fetch_assoc()["total"];


// Total available copies
$result = $conn->query(
    "SELECT COALESCE(SUM(available_quantity), 0) AS total
     FROM books"
);

$available_books = (int) $result->fetch_assoc()["total"];


// Student active issues
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ?
     AND status = 'issued'"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$active_issues = (int) $result->fetch_assoc()["total"];

$stmt->close();


// Total books borrowed by student
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$total_borrowed = (int) $result->fetch_assoc()["total"];

$stmt->close();


/* =====================================================
   CURRENTLY ISSUED BOOKS
===================================================== */

$stmt = $conn->prepare(
    "SELECT
        ib.id,
        b.book_code,
        b.title,
        a.author_name,
        c.category_name,
        ib.issue_date,
        ib.due_date,
        ib.fine
     FROM issued_books ib

     INNER JOIN books b
        ON ib.book_id = b.id

     LEFT JOIN authors a
        ON b.author_id = a.id

     LEFT JOIN categories c
        ON b.category_id = c.id

     WHERE ib.user_id = ?
     AND ib.status = 'issued'

     ORDER BY ib.due_date ASC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$current_books = $stmt->get_result();

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Student Dashboard | Library Management System
</title>


<style>

/* =====================================================
   GLOBAL
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;

    background: #f1f5f9;

    color: #0f172a;
}

a {
    text-decoration: none;
}


/* =====================================================
   LAYOUT
===================================================== */

.layout {

    display: flex;

    min-height: 100vh;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    width: 260px;

    background: #0f172a;

    color: white;

    position: fixed;

    top: 0;
    left: 0;
    bottom: 0;

    padding: 22px 16px;

    overflow-y: auto;

    z-index: 100;
}


/* BRAND */

.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 5px 12px 28px;

    border-bottom: 1px solid #1e293b;

}

.brand-icon {

    width: 42px;
    height: 42px;

    border-radius: 12px;

    background: #2563eb;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 20px;

}

.brand-text h2 {

    font-size: 17px;

    font-weight: 700;

}

.brand-text p {

    margin-top: 3px;

    color: #94a3b8;

    font-size: 11px;

}


/* NAV TITLE */

.nav-title {

    margin-top: 24px;

    padding: 0 12px 8px;

    color: #64748b;

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 1px;

    text-transform: uppercase;

}


/* NAV ITEM */

.nav-item {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 11px 12px;

    margin-bottom: 4px;

    border-radius: 9px;

    color: #cbd5e1;

    font-size: 13px;

    font-weight: 500;

    transition: 0.2s;

}

.nav-item:hover {

    background: #1e293b;

    color: white;

}

.nav-item.active {

    background: #2563eb;

    color: white;

}

.nav-icon {

    width: 21px;

    text-align: center;

    font-size: 16px;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 260px;

    width: calc(100% - 260px);

    min-height: 100vh;

}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {

    height: 74px;

    background: white;

    border-bottom: 1px solid #e2e8f0;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;

    position: sticky;

    top: 0;

    z-index: 50;

}

.page-title h1 {

    font-size: 21px;

    font-weight: 700;

}

.page-title p {

    margin-top: 3px;

    color: #64748b;

    font-size: 12px;

}


/* PROFILE */

.profile {

    display: flex;

    align-items: center;

    gap: 10px;

}

.profile-avatar {

    width: 39px;
    height: 39px;

    border-radius: 50%;

    background: #dbeafe;

    color: #2563eb;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 14px;

    font-weight: 700;

}

.profile-details strong {

    display: block;

    font-size: 13px;

}

.profile-details span {

    display: block;

    margin-top: 2px;

    color: #64748b;

    font-size: 10px;

}


/* =====================================================
   CONTENT
===================================================== */

.content {

    padding: 28px 30px;

}


/* =====================================================
   WELCOME
===================================================== */

.welcome {

    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #2563eb
        );

    border-radius: 16px;

    color: white;

    padding: 24px 26px;

    margin-bottom: 24px;

    box-shadow:
        0 8px 22px rgba(37, 99, 235, 0.15);

}

.welcome h2 {

    font-size: 21px;

    margin-bottom: 6px;

}

.welcome p {

    color: #dbeafe;

    font-size: 12px;

}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 17px;

    margin-bottom: 25px;

}

.stat-card {

    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 14px;

    padding: 18px;

    box-shadow:
        0 3px 10px rgba(15, 23, 42, 0.03);

}

.stat-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 13px;

}

.stat-icon {

    width: 40px;
    height: 40px;

    border-radius: 10px;

    background: #eff6ff;

    color: #2563eb;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 17px;

}

.stat-label {

    color: #64748b;

    font-size: 11px;

    font-weight: 600;

}

.stat-value {

    font-size: 26px;

    font-weight: 750;

}


/* =====================================================
   SECTION
===================================================== */

.section {

    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 14px;

    margin-bottom: 24px;

    overflow: hidden;

    box-shadow:
        0 3px 10px rgba(15, 23, 42, 0.03);

}

.section-header {

    padding: 18px 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    border-bottom: 1px solid #e2e8f0;

}

.section-header h3 {

    font-size: 15px;

    font-weight: 700;

}

.section-header p {

    margin-top: 3px;

    color: #64748b;

    font-size: 11px;

}

.section-link {

    color: #2563eb;

    font-size: 11px;

    font-weight: 600;

}


/* =====================================================
   TABLE
===================================================== */

.table-container {

    width: 100%;

    overflow-x: auto;

}

table {

    width: 100%;

    min-width: 800px;

    border-collapse: collapse;

}

th {

    background: #f8fafc;

    color: #475569;

    padding: 12px 16px;

    text-align: left;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.4px;

    border-bottom: 1px solid #e2e8f0;

}

td {

    padding: 13px 16px;

    color: #334155;

    font-size: 11px;

    border-bottom: 1px solid #f1f5f9;

}

tbody tr:hover {

    background: #f8fafc;

}

tbody tr:last-child td {

    border-bottom: none;

}

.book-code {

    color: #2563eb;

    font-weight: 700;

}

.book-title {

    color: #0f172a;

    font-weight: 600;

}


/* =====================================================
   STATUS
===================================================== */

.status {

    display: inline-flex;

    align-items: center;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 700;

}

.status-issued {

    background: #fef3c7;

    color: #92400e;

}

.status-overdue {

    background: #fee2e2;

    color: #991b1b;

}

.fine {

    font-weight: 700;

}

.fine-zero {

    color: #16a34a;

}

.fine-due {

    color: #dc2626;

}


/* =====================================================
   QUICK ACTIONS
===================================================== */

.quick-actions {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 14px;

    padding: 18px;

}

.quick-action {

    display: flex;

    align-items: center;

    gap: 11px;

    padding: 15px;

    border: 1px solid #e2e8f0;

    border-radius: 11px;

    color: #0f172a;

    transition: 0.2s;

}

.quick-action:hover {

    background: #eff6ff;

    border-color: #93c5fd;

}

.quick-icon {

    width: 38px;
    height: 38px;

    border-radius: 9px;

    background: #eff6ff;

    color: #2563eb;

    display: flex;

    align-items: center;
    justify-content: center;

}

.quick-action strong {

    display: block;

    font-size: 12px;

}

.quick-action span {

    display: block;

    color: #64748b;

    font-size: 10px;

    margin-top: 3px;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 40px 20px;

}

.empty-icon {

    width: 48px;
    height: 48px;

    margin: 0 auto 10px;

    border-radius: 12px;

    background: #eff6ff;

    color: #2563eb;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 20px;

}

.empty h4 {

    font-size: 13px;

    margin-bottom: 4px;

}

.empty p {

    color: #64748b;

    font-size: 11px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media (max-width: 768px) {

    .sidebar {

        width: 220px;

    }

    .main {

        margin-left: 220px;

        width: calc(100% - 220px);

    }

    .content {

        padding: 20px;

    }

    .topbar {

        padding: 0 20px;

    }

    .quick-actions {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 600px) {

    .sidebar {

        position: relative;

        width: 100%;

        min-height: auto;

    }

    .main {

        margin-left: 0;

        width: 100%;

    }

    .layout {

        display: block;

    }

    .stats {

        grid-template-columns: 1fr;

    }

    .topbar {

        position: relative;

    }

    .content {

        padding: 15px;

    }

    .profile-details {

        display: none;

    }

}

</style>

</head>


<body>


<div class="layout">


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <!-- BRAND -->

    <div class="brand">

        <div class="brand-icon">
            📚
        </div>

        <div class="brand-text">

            <h2>
                Library System
            </h2>

            <p>
                Student Portal
            </p>

        </div>

    </div>


    <!-- MAIN MENU -->

    <div class="nav-title">
        Main Menu
    </div>


    <a
        href="dashboard.php"
        class="nav-item active"
    >

        <span class="nav-icon">
            📊
        </span>

        Dashboard

    </a>


    <a
        href="books.php"
        class="nav-item"
    >

        <span class="nav-icon">
            📚
        </span>

        Browse Books

    </a>


    <!-- MY LIBRARY -->

    <div class="nav-title">
        My Library
    </div>


    <a
        href="issued_books.php"
        class="nav-item"
    >

        <span class="nav-icon">
            📖
        </span>

        My Issued Books

    </a>


    <a
        href="history.php"
        class="nav-item"
    >

        <span class="nav-icon">
            🕘
        </span>

        Book History

    </a>


    <!-- ACCOUNT -->

    <div class="nav-title">
        Account
    </div>


    <a
        href="account.php"
        class="nav-item"
    >

        <span class="nav-icon">
            👤
        </span>

        My Account

    </a>


    <a
        href="../auth/logout.php"
        class="nav-item"
    >

        <span class="nav-icon">
            🚪
        </span>

        Logout

    </a>


</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">


        <div class="page-title">

            <h1>
                Student Dashboard
            </h1>

            <p>
                Library Management System
            </p>

        </div>


        <div class="profile">


            <div class="profile-avatar">

                <?= htmlspecialchars(
                    strtoupper(
                        substr($user_name, 0, 1)
                    )
                ) ?>

            </div>


            <div class="profile-details">

                <strong>
                    <?= htmlspecialchars($user_name) ?>
                </strong>

                <span>
                    Student
                </span>

            </div>


        </div>


    </header>


    <!-- CONTENT -->

    <div class="content">


        <!-- WELCOME -->

        <div class="welcome">

            <h2>
                Welcome back,
                <?= htmlspecialchars($user_name) ?> 👋
            </h2>

            <p>
                View your library activity and manage your books
                from your student dashboard.
            </p>

        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="stats">


            <!-- TOTAL BOOKS -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Total Book Titles
                    </span>

                    <div class="stat-icon">
                        📚
                    </div>

                </div>

                <div class="stat-value">
                    <?= $total_books ?>
                </div>

            </div>


            <!-- AVAILABLE -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Available Copies
                    </span>

                    <div class="stat-icon">
                        ✅
                    </div>

                </div>

                <div class="stat-value">
                    <?= $available_books ?>
                </div>

            </div>


            <!-- ACTIVE -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        My Active Issues
                    </span>

                    <div class="stat-icon">
                        📖
                    </div>

                </div>

                <div class="stat-value">
                    <?= $active_issues ?>
                </div>

            </div>


            <!-- TOTAL BORROWED -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Total Borrowed
                    </span>

                    <div class="stat-icon">
                        🕘
                    </div>

                </div>

                <div class="stat-value">
                    <?= $total_borrowed ?>
                </div>

            </div>


        </div>


        <!-- =================================================
             CURRENTLY ISSUED BOOKS
        ================================================== -->

        <section class="section">


            <div class="section-header">

                <div>

                    <h3>
                        Currently Issued Books
                    </h3>

                    <p>
                        Books currently issued to your account
                    </p>

                </div>


                <a
                    href="issued_books.php"
                    class="section-link"
                >
                    View All →
                </a>

            </div>


            <?php if ($current_books->num_rows > 0): ?>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Book Code
                                </th>

                                <th>
                                    Book Title
                                </th>

                                <th>
                                    Author
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Issue Date
                                </th>

                                <th>
                                    Due Date
                                </th>

                                <th>
                                    Fine
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php while (
                            $book =
                            $current_books->fetch_assoc()
                        ): ?>


                            <?php

                            $today = new DateTime();

                            $due_date =
                                new DateTime(
                                    $book["due_date"]
                                );

                            $overdue =
                                $today > $due_date;

                            ?>


                            <tr>


                                <td>

                                    <span class="book-code">

                                        <?= htmlspecialchars(
                                            $book["book_code"]
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="book-title">

                                        <?= htmlspecialchars(
                                            $book["title"]
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $book["author_name"]
                                        ?? "-"
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $book["category_name"]
                                        ?? "-"
                                    ) ?>

                                </td>


                                <td>

                                    <?= date(
                                        "d-m-Y",
                                        strtotime(
                                            $book["issue_date"]
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= date(
                                        "d-m-Y",
                                        strtotime(
                                            $book["due_date"]
                                        )
                                    ) ?>

                                </td>


                                <td>


                                    <?php if (
                                        (float)$book["fine"] > 0
                                    ): ?>

                                        <span class="fine fine-due">

                                            ₹<?= number_format(
                                                (float)$book["fine"],
                                                2
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="fine fine-zero">

                                            ₹0.00

                                        </span>

                                    <?php endif; ?>


                                </td>


                                <td>


                                    <?php if ($overdue): ?>

                                        <span
                                            class="status status-overdue"
                                        >
                                            OVERDUE
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="status status-issued"
                                        >
                                            ISSUED
                                        </span>

                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty">

                    <div class="empty-icon">
                        📖
                    </div>

                    <h4>
                        No books currently issued
                    </h4>

                    <p>
                        You don't have any active book issues.
                    </p>

                </div>


            <?php endif; ?>


        </section>


        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <section class="section">


            <div class="section-header">

                <div>

                    <h3>
                        Quick Actions
                    </h3>

                    <p>
                        Frequently used library functions
                    </p>

                </div>

            </div>


            <div class="quick-actions">


                <a
                    href="books.php"
                    class="quick-action"
                >

                    <div class="quick-icon">
                        📚
                    </div>

                    <div>

                        <strong>
                            Browse Books
                        </strong>

                        <span>
                            View all library books
                        </span>

                    </div>

                </a>


                <a
                    href="issued_books.php"
                    class="quick-action"
                >

                    <div class="quick-icon">
                        📖
                    </div>

                    <div>

                        <strong>
                            My Issued Books
                        </strong>

                        <span>
                            View currently issued books
                        </span>

                    </div>

                </a>


                <a
                    href="account.php"
                    class="quick-action"
                >

                    <div class="quick-icon">
                        👤
                    </div>

                    <div>

                        <strong>
                            My Account
                        </strong>

                        <span>
                            View account information
                        </span>

                    </div>

                </a>


            </div>


        </section>


    </div>


</main>


</div>


</body>

</html>