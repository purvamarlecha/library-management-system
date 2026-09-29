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
   CURRENTLY ISSUED BOOKS
===================================================== */

$stmt = $conn->prepare(
    "SELECT
        ib.id,
        b.id AS book_id,
        b.book_code,
        b.title,
        b.isbn,
        a.author_name,
        c.category_name,
        b.shelf_number,
        ib.issue_date,
        ib.due_date,
        ib.fine,
        ib.status
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

$issued_books = $stmt->get_result();


/* =====================================================
   COUNT ACTIVE BOOKS
===================================================== */

$active_count = $issued_books->num_rows;


/* =====================================================
   CALCULATE TOTAL FINE
===================================================== */

$total_fine = 0;

$rows = [];

while ($row = $issued_books->fetch_assoc()) {

    $today = new DateTime();
    $due_date = new DateTime($row["due_date"]);

    $is_overdue = $today > $due_date;

    $row["is_overdue"] = $is_overdue;

    $total_fine += (float) $row["fine"];

    $rows[] = $row;
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

<title>
    My Issued Books | Library Management System
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
   PAGE HEADER
===================================================== */

.page-header {

    margin-bottom: 24px;

}

.page-header h2 {

    font-size: 22px;

    font-weight: 700;

}

.page-header p {

    margin-top: 5px;

    color: #64748b;

    font-size: 12px;

}


/* =====================================================
   SUMMARY
===================================================== */

.summary-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 17px;

    margin-bottom: 24px;

}

.summary-card {

    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 14px;

    padding: 18px;

    box-shadow:
        0 3px 10px rgba(15, 23, 42, 0.03);

}

.summary-label {

    color: #64748b;

    font-size: 11px;

    font-weight: 600;

    margin-bottom: 8px;

}

.summary-value {

    font-size: 25px;

    font-weight: 750;

}

.summary-value.blue {

    color: #2563eb;

}

.summary-value.red {

    color: #dc2626;

}


/* =====================================================
   TABLE SECTION
===================================================== */

.section {

    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 14px;

    overflow: hidden;

    box-shadow:
        0 3px 10px rgba(15, 23, 42, 0.03);

}

.section-header {

    padding: 18px 20px;

    border-bottom: 1px solid #e2e8f0;

}

.section-header h3 {

    font-size: 15px;

    font-weight: 700;

}

.section-header p {

    margin-top: 4px;

    color: #64748b;

    font-size: 11px;

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

    min-width: 1100px;

    border-collapse: collapse;

}

th {

    background: #f8fafc;

    color: #475569;

    padding: 12px 15px;

    text-align: left;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.4px;

    border-bottom: 1px solid #e2e8f0;

}

td {

    padding: 13px 15px;

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


/* =====================================================
   FINE
===================================================== */

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
   VIEW BUTTON
===================================================== */

.view-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 6px 10px;

    border-radius: 7px;

    background: #eff6ff;

    color: #2563eb;

    font-size: 10px;

    font-weight: 600;

    white-space: nowrap;

}

.view-button:hover {

    background: #dbeafe;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 55px 20px;

}

.empty-icon {

    width: 52px;
    height: 52px;

    margin: 0 auto 12px;

    border-radius: 12px;

    background: #eff6ff;

    color: #2563eb;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 22px;

}

.empty h4 {

    font-size: 14px;

    margin-bottom: 5px;

}

.empty p {

    color: #64748b;

    font-size: 11px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

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

}


@media (max-width: 600px) {

    .sidebar {

        position: relative;

        width: 100%;

    }

    .layout {

        display: block;

    }

    .main {

        margin-left: 0;

        width: 100%;

    }

    .summary-grid {

        grid-template-columns: 1fr;

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
        class="nav-item"
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
        class="nav-item active"
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
                My Issued Books
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


        <!-- PAGE HEADER -->

        <div class="page-header">

            <h2>
                My Issued Books
            </h2>

            <p>
                View all books currently issued to your account.
            </p>

        </div>


        <!-- =================================================
             SUMMARY
        ================================================== -->

        <div class="summary-grid">


            <div class="summary-card">

                <div class="summary-label">
                    Currently Issued
                </div>

                <div class="summary-value blue">
                    <?= $active_count ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Total Fine
                </div>

                <div
                    class="
                    summary-value
                    <?= $total_fine > 0
                        ? 'red'
                        : 'blue'
                    ?>
                    "
                >

                    ₹<?= number_format(
                        $total_fine,
                        2
                    ) ?>

                </div>

            </div>


        </div>


        <!-- =================================================
             TABLE
        ================================================== -->

        <section class="section">


            <div class="section-header">

                <h3>
                    Currently Issued Books
                </h3>

                <p>
                    Records retrieved from the issued_books database table.
                </p>

            </div>


            <?php if (count($rows) > 0): ?>


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
                                    ISBN
                                </th>

                                <th>
                                    Shelf
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

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($rows as $book): ?>


                            <tr>


                                <!-- BOOK CODE -->

                                <td>

                                    <span class="book-code">

                                        <?= htmlspecialchars(
                                            $book["book_code"]
                                        ) ?>

                                    </span>

                                </td>


                                <!-- TITLE -->

                                <td>

                                    <span class="book-title">

                                        <?= htmlspecialchars(
                                            $book["title"]
                                        ) ?>

                                    </span>

                                </td>


                                <!-- AUTHOR -->

                                <td>

                                    <?= htmlspecialchars(
                                        $book["author_name"]
                                        ?? "-"
                                    ) ?>

                                </td>


                                <!-- CATEGORY -->

                                <td>

                                    <?= htmlspecialchars(
                                        $book["category_name"]
                                        ?? "-"
                                    ) ?>

                                </td>


                                <!-- ISBN -->

                                <td>

                                    <?= htmlspecialchars(
                                        $book["isbn"]
                                        ?? "-"
                                    ) ?>

                                </td>


                                <!-- SHELF -->

                                <td>

                                    <?= htmlspecialchars(
                                        $book["shelf_number"]
                                        ?? "-"
                                    ) ?>

                                </td>


                                <!-- ISSUE DATE -->

                                <td>

                                    <?= date(
                                        "d-m-Y",
                                        strtotime(
                                            $book["issue_date"]
                                        )
                                    ) ?>

                                </td>


                                <!-- DUE DATE -->

                                <td>

                                    <?= date(
                                        "d-m-Y",
                                        strtotime(
                                            $book["due_date"]
                                        )
                                    ) ?>

                                </td>


                                <!-- FINE -->

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


                                <!-- STATUS -->

                                <td>


                                    <?php if (
                                        $book["is_overdue"]
                                    ): ?>

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


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="book_details.php?id=<?= (int)$book["book_id"] ?>"
                                        class="view-button"
                                    >
                                        View
                                    </a>

                                </td>


                            </tr>


                        <?php endforeach; ?>


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
                        You currently have no active book issues.
                    </p>


                </div>


            <?php endif; ?>


        </section>


    </div>


</main>


</div>


</body>

</html>