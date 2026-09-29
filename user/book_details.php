<?php
session_start();
require_once "../config/database.php";

/* =========================
   ACCESS PROTECTION
========================= */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION["user_role"] !== "student") {
    header("Location: ../admin/dashboard.php");
    exit();
}

/* =========================
   GET BOOK ID
========================= */
$book_id = intval($_GET["id"] ?? 0);

if ($book_id <= 0) {
    header("Location: books.php");
    exit();
}

/* =========================
   GET BOOK DETAILS
========================= */
$stmt = $conn->prepare(
    "SELECT
        b.id,
        b.book_code,
        b.title,
        b.isbn,
        b.publisher,
        b.publication_year,
        b.quantity,
        b.available_quantity,
        b.shelf_number,
        b.qr_code,
        b.created_at,
        a.author_name,
        c.category_name
     FROM books b
     LEFT JOIN authors a
        ON b.author_id = a.id
     LEFT JOIN categories c
        ON b.category_id = c.id
     WHERE b.id = ?"
);

$stmt->bind_param("i", $book_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: books.php");
    exit();
}

$book = $result->fetch_assoc();

$stmt->close();

/* =========================
   BOOK STATUS
========================= */
$available_quantity = (int)$book["available_quantity"];
$total_quantity = (int)$book["quantity"];

$is_available = $available_quantity > 0;

/* =========================
   INITIAL QR VALUE
========================= */
$qr_value = !empty($book["book_code"])
    ? $book["book_code"]
    : "BOOK-" . $book["id"];
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
        Book Details | Library Management System
    </title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }

        /* =========================
           LAYOUT
        ========================= */

        .layout {
            min-height: 100vh;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;

            width: 250px;

            background: #0f172a;

            color: white;

            padding: 24px 16px;

            overflow-y: auto;
        }

        .brand {
            display: flex;
            align-items: center;

            gap: 12px;

            padding: 8px 10px 28px;
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

        .brand h2 {
            font-size: 17px;
            line-height: 1.2;
        }

        .brand span {
            display: block;

            margin-top: 4px;

            color: #94a3b8;

            font-size: 11px;
        }

        .nav-title {
            margin: 18px 10px 8px;

            color: #64748b;

            font-size: 11px;

            font-weight: bold;

            text-transform: uppercase;
        }

        .nav a {
            display: flex;
            align-items: center;

            gap: 12px;

            padding: 12px 13px;

            margin-bottom: 5px;

            border-radius: 10px;

            color: #cbd5e1;

            text-decoration: none;

            font-size: 14px;

            transition: 0.2s;
        }

        .nav a:hover {
            background: #1e293b;

            color: white;
        }

        .nav a.active {
            background: #2563eb;

            color: white;
        }

        .nav-icon {
            width: 22px;

            text-align: center;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 250px;

            width: calc(100% - 250px);

            padding: 30px;
        }

        /* =========================
           TOPBAR
        ========================= */

        .topbar {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;
        }

        .page-title h1 {
            color: #0f172a;

            font-size: 27px;

            margin-bottom: 5px;
        }

        .page-title p {
            color: #64748b;

            font-size: 14px;
        }

        .profile {
            display: flex;

            align-items: center;

            gap: 12px;

            background: white;

            padding: 9px 14px;

            border-radius: 14px;

            box-shadow:
                0 5px 20px rgba(15, 23, 42, 0.06);
        }

        .avatar {
            width: 40px;
            height: 40px;

            border-radius: 50%;

            background: #dbeafe;

            color: #2563eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: bold;
        }

        .profile strong {
            display: block;

            font-size: 13px;
        }

        .profile span {
            color: #64748b;

            font-size: 11px;
        }

        /* =========================
           BACK BUTTON
        ========================= */

        .back-button {
            display: inline-flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 20px;

            padding: 10px 14px;

            border-radius: 10px;

            background: white;

            color: #475569;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            box-shadow:
                0 5px 20px rgba(15, 23, 42, 0.05);
        }

        .back-button:hover {
            color: #2563eb;
        }

        /* =========================
           BOOK HEADER
        ========================= */

        .book-header-card {
            background: white;

            border-radius: 18px;

            padding: 25px;

            margin-bottom: 20px;

            box-shadow:
                0 6px 25px rgba(15, 23, 42, 0.06);
        }

        .book-header-content {
            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;
        }

        .book-heading {
            display: flex;

            gap: 16px;

            align-items: flex-start;
        }

        .book-icon {
            width: 60px;
            height: 72px;

            border-radius: 12px;

            background: #eff6ff;

            color: #2563eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 30px;

            flex-shrink: 0;
        }

        .book-heading h2 {
            color: #0f172a;

            font-size: 23px;

            margin-bottom: 7px;
        }

        .book-heading p {
            color: #64748b;

            font-size: 13px;
        }

        .status {
            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 8px 12px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

            white-space: nowrap;
        }

        .status.available {
            background: #dcfce7;

            color: #15803d;
        }

        .status.unavailable {
            background: #fee2e2;

            color: #b91c1c;
        }

        .status-dot {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: currentColor;
        }

        /* =========================
           CONTENT GRID
        ========================= */

        .content-grid {
            display: grid;

            grid-template-columns: 1fr 320px;

            gap: 20px;

            align-items: start;
        }

        /* =========================
           DETAILS TABLE
        ========================= */

        .details-card {
            background: white;

            border-radius: 18px;

            overflow: hidden;

            box-shadow:
                0 6px 25px rgba(15, 23, 42, 0.06);
        }

        .card-header {
            padding: 19px 20px;

            border-bottom: 1px solid #eef2f7;
        }

        .card-header h3 {
            color: #0f172a;

            font-size: 17px;
        }

        .card-header p {
            margin-top: 4px;

            color: #64748b;

            font-size: 12px;
        }

        .details-table {
            width: 100%;

            border-collapse: collapse;
        }

        .details-table tr {
            border-bottom: 1px solid #eef2f7;
        }

        .details-table tr:last-child {
            border-bottom: none;
        }

        .details-table td {
            padding: 16px 20px;

            font-size: 13px;
        }

        .details-table td:first-child {
            width: 38%;

            color: #64748b;

            font-weight: 600;

            background: #fafbfc;
        }

        .details-table td:last-child {
            color: #172033;

            font-weight: 500;
        }

        .code-value {
            display: inline-block;

            padding: 6px 9px;

            border-radius: 7px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 11px;

            font-weight: bold;
        }

        /* =========================
           AVAILABILITY CARD
        ========================= */

        .availability-card {
            background: white;

            border-radius: 18px;

            padding: 20px;

            margin-bottom: 20px;

            box-shadow:
                0 6px 25px rgba(15, 23, 42, 0.06);
        }

        .availability-card h3 {
            color: #0f172a;

            font-size: 17px;

            margin-bottom: 18px;
        }

        .availability-number {
            text-align: center;

            padding: 15px;

            border-radius: 14px;

            background: #f8fafc;

            margin-bottom: 15px;
        }

        .availability-number strong {
            display: block;

            color: #0f172a;

            font-size: 30px;

            margin-bottom: 4px;
        }

        .availability-number span {
            color: #64748b;

            font-size: 12px;
        }

        .availability-bar {
            width: 100%;

            height: 9px;

            background: #e2e8f0;

            border-radius: 20px;

            overflow: hidden;

            margin-bottom: 8px;
        }

        .availability-fill {
            height: 100%;

            background: #2563eb;

            border-radius: 20px;

            transition: width 0.3s;
        }

        .availability-info {
            display: flex;

            justify-content: space-between;

            color: #64748b;

            font-size: 11px;
        }

        /* =========================
           QR CARD
        ========================= */

        .qr-card {
            background: white;

            border-radius: 18px;

            padding: 20px;

            box-shadow:
                0 6px 25px rgba(15, 23, 42, 0.06);
        }

        .qr-card h3 {
            color: #0f172a;

            font-size: 17px;

            margin-bottom: 5px;
        }

        .qr-card > p {
            color: #64748b;

            font-size: 12px;

            margin-bottom: 18px;
        }

        .qr-box {
            min-height: 220px;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 15px;

            border: 1px dashed #cbd5e1;

            border-radius: 14px;

            background: #f8fafc;
        }

        #qrcode img {
            margin: auto;
        }

        .qr-code-text {
            text-align: center;

            margin-top: 12px;

            color: #2563eb;

            font-size: 12px;

            font-weight: bold;
        }

        .qr-note {
            margin-top: 12px;

            color: #94a3b8;

            font-size: 11px;

            line-height: 1.5;

            text-align: center;
        }

        /* =========================
           ACTIONS
        ========================= */

        .actions {
            display: flex;

            gap: 10px;

            margin-top: 20px;
        }

        .action-button {
            flex: 1;

            height: 44px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;
        }

        .primary-action {
            background: #2563eb;

            color: white;
        }

        .primary-action:hover {
            background: #1d4ed8;
        }

        .secondary-action {
            background: #f1f5f9;

            color: #475569;
        }

        .secondary-action:hover {
            background: #e2e8f0;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .content-grid {
                grid-template-columns: 1fr;
            }

            .qr-card,
            .availability-card {
                max-width: none;
            }

        }

        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;

                width: calc(100% - 210px);

                padding: 20px;
            }

            .topbar {
                align-items: flex-start;

                flex-direction: column;
            }

            .profile {
                width: 100%;
            }

        }

        @media (max-width: 650px) {

            .layout {
                display: block;
            }

            .sidebar {
                position: relative;

                width: 100%;

                height: auto;

                min-height: auto;
            }

            .main {
                margin-left: 0;

                width: 100%;

                padding: 15px;
            }

            .book-header-content {
                flex-direction: column;
            }

            .book-heading h2 {
                font-size: 20px;
            }

            .details-table td {
                padding: 13px;
            }

            .details-table td:first-child {
                width: 42%;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-icon">
                📚
            </div>

            <div>
                <h2>Library System</h2>
                <span>Student Portal</span>
            </div>

        </div>


        <div class="nav-title">
            Main Menu
        </div>


        <nav class="nav">

            <a href="dashboard.php">

                <span class="nav-icon">
                    🏠
                </span>

                Dashboard

            </a>


            <a href="books.php" class="active">

                <span class="nav-icon">
                    📚
                </span>

                Browse Books

            </a>

        </nav>


        <div class="nav-title">
            Account
        </div>


        <nav class="nav">

            <a href="../auth/logout.php">

                <span class="nav-icon">
                    🚪
                </span>

                Logout

            </a>

        </nav>

    </aside>


    <!-- =========================
         MAIN
    ========================== -->

    <main class="main">

        <!-- TOPBAR -->

        <div class="topbar">

            <div class="page-title">

                <h1>
                    Book Details
                </h1>

                <p>
                    View complete information for this library record.
                </p>

            </div>


            <div class="profile">

                <div class="avatar">

                    <?= strtoupper(
                        substr($_SESSION["user_name"], 0, 1)
                    ) ?>

                </div>

                <div>

                    <strong>
                        <?= htmlspecialchars(
                            $_SESSION["user_name"]
                        ) ?>
                    </strong>

                    <span>
                        Student
                    </span>

                </div>

            </div>

        </div>


        <!-- BACK -->

        <a
            href="books.php"
            class="back-button"
        >
            ← Back to Books
        </a>


        <!-- =========================
             BOOK HEADER
        ========================== -->

        <div class="book-header-card">

            <div class="book-header-content">

                <div class="book-heading">

                    <div class="book-icon">
                        📖
                    </div>

                    <div>

                        <h2>
                            <?= htmlspecialchars(
                                $book["title"]
                            ) ?>
                        </h2>

                        <p>

                            Book Code:

                            <strong>
                                <?= htmlspecialchars(
                                    $book["book_code"]
                                ) ?>
                            </strong>

                            &nbsp; • &nbsp;

                            <?= htmlspecialchars(
                                $book["author_name"]
                                ?? "Unknown Author"
                            ) ?>

                        </p>

                    </div>

                </div>


                <?php if ($is_available): ?>

                    <span class="status available">

                        <span class="status-dot"></span>

                        Available

                    </span>

                <?php else: ?>

                    <span class="status unavailable">

                        <span class="status-dot"></span>

                        Currently Unavailable

                    </span>

                <?php endif; ?>

            </div>

        </div>


        <!-- =========================
             CONTENT
        ========================== -->

        <div class="content-grid">


            <!-- =========================
                 DATABASE RECORD
            ========================== -->

            <div class="details-card">

                <div class="card-header">

                    <h3>
                        Book Database Record
                    </h3>

                    <p>
                        Complete information stored for this book.
                    </p>

                </div>


                <table class="details-table">

                    <tr>

                        <td>
                            Book ID
                        </td>

                        <td>
                            <?= (int)$book["id"] ?>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            Book Code
                        </td>

                        <td>

                            <span class="code-value">

                                <?= htmlspecialchars(
                                    $book["book_code"]
                                ) ?>

                            </span>

                        </td>

                    </tr>


                    <tr>

                        <td>
                            Title
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $book["title"]
                            ) ?>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            Author
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $book["author_name"]
                                ?? "Unknown Author"
                            ) ?>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            Category
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $book["category_name"]
                                ?? "Uncategorized"
                            ) ?>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            ISBN
                        </td>

                        <td>

                            <?= !empty($book["isbn"])
                                ? htmlspecialchars(
                                    $book["isbn"]
                                )
                                : "Not Available"
                            ?>

                        </td>

                    </tr>


                    <tr>

                        <td>
                            Publisher
                        </td>

                        <td>

                            <?= !empty($book["publisher"])
                                ? htmlspecialchars(
                                    $book["publisher"]
                                )
                                : "Not Available"
                            ?>

                        </td>

                    </tr>


                    <tr>

                        <td>
                            Publication Year
                        </td>

                        <td>

                            <?= !empty(
                                $book["publication_year"]
                            )
                                ? htmlspecialchars(
                                    $book["publication_year"]
                                )
                                : "Not Available"
                            ?>

                        </td>

                    </tr>


                    <tr>

                        <td>
                            Shelf Number
                        </td>

                        <td>

                            <?= !empty(
                                $book["shelf_number"]
                            )
                                ? htmlspecialchars(
                                    $book["shelf_number"]
                                )
                                : "Not Assigned"
                            ?>

                        </td>

                    </tr>


                    <tr>

                        <td>
                            Total Quantity
                        </td>

                        <td>
                            <?= $total_quantity ?>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            Available Quantity
                        </td>

                        <td>
                            <?= $available_quantity ?>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            Database Status
                        </td>

                        <td>

                            <?php if ($is_available): ?>

                                <span class="status available">

                                    <span class="status-dot"></span>

                                    Available for Issue

                                </span>

                            <?php else: ?>

                                <span class="status unavailable">

                                    <span class="status-dot"></span>

                                    All Copies Issued

                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                </table>

            </div>


            <!-- =========================
                 RIGHT SIDE
            ========================== -->

            <div>


                <!-- AVAILABILITY -->

                <div class="availability-card">

                    <h3>
                        Availability
                    </h3>


                    <div class="availability-number">

                        <strong>
                            <?= $available_quantity ?>
                        </strong>

                        <span>
                            Available out of
                            <?= $total_quantity ?>
                            copies
                        </span>

                    </div>


                    <?php

                    $percentage = 0;

                    if ($total_quantity > 0) {
                        $percentage =
                            ($available_quantity /
                            $total_quantity) * 100;
                    }

                    ?>


                    <div class="availability-bar">

                        <div
                            class="availability-fill"
                            style="width: <?= $percentage ?>%;"
                        ></div>

                    </div>


                    <div class="availability-info">

                        <span>
                            Available
                        </span>

                        <span>
                            <?= round($percentage) ?>%
                        </span>

                    </div>

                </div>


                <!-- QR -->

                <div class="qr-card">

                    <h3>
                        Book QR Code
                    </h3>

                    <p>
                        QR identifier associated with this book.
                    </p>


                    <div class="qr-box">

                        <div id="qrcode"></div>

                    </div>


                    <div class="qr-code-text">

                        <?= htmlspecialchars($qr_value) ?>

                    </div>


                    <p class="qr-note">

                        This QR code uses the book code as
                        the unique identifier for the library
                        system.

                    </p>


                    <div class="actions">

                        <a
                            href="books.php"
                            class="action-button secondary-action"
                        >
                            ← Back
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>


<!-- =========================
     QR CODE LIBRARY
========================= -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>


<script>

    new QRCode(
        document.getElementById("qrcode"),
        {
            text: <?= json_encode($qr_value) ?>,
            width: 180,
            height: 180,
            correctLevel: QRCode.CorrectLevel.H
        }
    );

</script>

</body>

</html>