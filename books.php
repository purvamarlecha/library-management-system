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
   SEARCH & FILTER
========================= */
$search = trim($_GET["search"] ?? "");
$category_id = intval($_GET["category"] ?? 0);

/* =========================
   GET CATEGORIES
========================= */
$categories = [];

$category_result = $conn->query(
    "SELECT id, category_name
     FROM categories
     ORDER BY category_name ASC"
);

if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

/* =========================
   GET BOOKS
========================= */
$books = [];

$sql = "SELECT
            b.id,
            b.book_code,
            b.title,
            b.isbn,
            b.publisher,
            b.publication_year,
            b.quantity,
            b.available_quantity,
            b.shelf_number,
            a.author_name,
            c.category_name
        FROM books b
        LEFT JOIN authors a
            ON b.author_id = a.id
        LEFT JOIN categories c
            ON b.category_id = c.id
        WHERE 1=1";

$params = [];
$types = "";

if ($search !== "") {

    $sql .= " AND (
        b.book_code LIKE ?
        OR b.title LIKE ?
        OR b.isbn LIKE ?
        OR a.author_name LIKE ?
        OR c.category_name LIKE ?
    )";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sssss";
}

if ($category_id > 0) {

    $sql .= " AND b.category_id = ?";

    $params[] = $category_id;
    $types .= "i";
}

$sql .= " ORDER BY b.title ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

$stmt->close();

/* =========================
   STATISTICS
========================= */

$total_books = count($books);

$available_titles = 0;
$unavailable_titles = 0;
$total_copies = 0;
$total_available_copies = 0;

foreach ($books as $book) {

    $quantity = (int)$book["quantity"];
    $available = (int)$book["available_quantity"];

    $total_copies += $quantity;
    $total_available_copies += $available;

    if ($available > 0) {
        $available_titles++;
    } else {
        $unavailable_titles++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Browse Books | Library Management System</title>

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

            padding: 30px;

            width: calc(100% - 250px);
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
           STATISTICS
        ========================= */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 24px;
        }

        .stat-card {
            background: white;

            border-radius: 18px;

            padding: 20px;

            box-shadow:
                0 6px 25px rgba(15, 23, 42, 0.06);
        }

        .stat-content {
            display: flex;

            justify-content: space-between;

            align-items: center;
        }

        .stat-label {
            color: #64748b;

            font-size: 13px;

            margin-bottom: 8px;
        }

        .stat-number {
            color: #0f172a;

            font-size: 25px;

            font-weight: bold;
        }

        .stat-icon {
            width: 45px;
            height: 45px;

            border-radius: 12px;

            background: #eff6ff;

            color: #2563eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
        }

        /* =========================
           SEARCH PANEL
        ========================= */

        .filter-card {
            background: white;

            border-radius: 18px;

            padding: 20px;

            margin-bottom: 24px;

            box-shadow:
                0 6px 25px rgba(15, 23, 42, 0.06);
        }

        .filter-header {
            margin-bottom: 15px;
        }

        .filter-header h2 {
            color: #0f172a;

            font-size: 18px;

            margin-bottom: 4px;
        }

        .filter-header p {
            color: #64748b;

            font-size: 12px;
        }

        .filter-form {
            display: grid;

            grid-template-columns:
                1fr 220px 110px 90px;

            gap: 10px;
        }

        .input,
        .select {
            width: 100%;

            height: 45px;

            border: 1px solid #dbe2ea;

            border-radius: 10px;

            padding: 0 13px;

            background: white;

            color: #172033;

            outline: none;

            font-size: 13px;
        }

        .input:focus,
        .select:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px #dbeafe;
        }

        .btn {
            height: 45px;

            border: none;

            border-radius: 10px;

            padding: 0 15px;

            cursor: pointer;

            text-decoration: none;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 13px;

            font-weight: 600;
        }

        .btn-primary {
            background: #2563eb;

            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-light {
            background: #f1f5f9;

            color: #475569;
        }

        .btn-light:hover {
            background: #e2e8f0;
        }

        /* =========================
           TABLE CARD
        ========================= */

        .table-card {
            background: white;

            border-radius: 18px;

            box-shadow:
                0 6px 25px rgba(15, 23, 42, 0.06);

            overflow: hidden;
        }

        .table-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 20px;

            border-bottom: 1px solid #eef2f7;
        }

        .table-header h2 {
            color: #0f172a;

            font-size: 18px;
        }

        .table-header span {
            color: #64748b;

            font-size: 12px;
        }

        .table-wrapper {
            width: 100%;

            overflow-x: auto;
        }

        table {
            width: 100%;

            min-width: 1050px;

            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
        }

        th {
            padding: 14px 16px;

            text-align: left;

            color: #64748b;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 0.3px;

            white-space: nowrap;

            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 15px 16px;

            border-bottom: 1px solid #eef2f7;

            color: #334155;

            font-size: 13px;

            vertical-align: middle;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .book-code {
            display: inline-block;

            padding: 5px 8px;

            border-radius: 7px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 11px;

            font-weight: bold;
        }

        .book-title {
            color: #0f172a;

            font-weight: 600;

            max-width: 220px;
        }

        .secondary {
            color: #64748b;

            font-size: 12px;
        }

        .quantity {
            font-weight: 600;

            color: #334155;
        }

        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 6px 9px;

            border-radius: 20px;

            font-size: 10px;

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

        .dot {
            width: 6px;
            height: 6px;

            border-radius: 50%;

            background: currentColor;
        }

        /* =========================
           EMPTY STATE
        ========================= */

        .empty {
            text-align: center;

            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 42px;

            margin-bottom: 12px;
        }

        .empty h3 {
            color: #0f172a;

            font-size: 18px;

            margin-bottom: 6px;
        }

        .empty p {
            color: #64748b;

            font-size: 13px;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 1100px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .filter-form {
                grid-template-columns:
                    1fr 1fr;
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

            .stats {
                grid-template-columns: 1fr;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .page-title h1 {
                font-size: 23px;
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
         MAIN CONTENT
    ========================== -->

    <main class="main">

        <!-- TOPBAR -->

        <div class="topbar">

            <div class="page-title">

                <h1>
                    Browse Books
                </h1>

                <p>
                    View and search the library book database.
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


        <!-- =========================
             STATISTICS
        ========================== -->

        <div class="stats">


            <div class="stat-card">

                <div class="stat-content">

                    <div>

                        <div class="stat-label">
                            Book Titles
                        </div>

                        <div class="stat-number">
                            <?= $total_books ?>
                        </div>

                    </div>

                    <div class="stat-icon">
                        📚
                    </div>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-content">

                    <div>

                        <div class="stat-label">
                            Total Copies
                        </div>

                        <div class="stat-number">
                            <?= $total_copies ?>
                        </div>

                    </div>

                    <div class="stat-icon">
                        📦
                    </div>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-content">

                    <div>

                        <div class="stat-label">
                            Available Copies
                        </div>

                        <div class="stat-number">
                            <?= $total_available_copies ?>
                        </div>

                    </div>

                    <div class="stat-icon">
                        🟢
                    </div>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-content">

                    <div>

                        <div class="stat-label">
                            Unavailable Titles
                        </div>

                        <div class="stat-number">
                            <?= $unavailable_titles ?>
                        </div>

                    </div>

                    <div class="stat-icon">
                        🔴
                    </div>

                </div>

            </div>

        </div>


        <!-- =========================
             SEARCH / FILTER
        ========================== -->

        <div class="filter-card">

            <div class="filter-header">

                <h2>
                    Search Library
                </h2>

                <p>
                    Search by book code, title, ISBN, author or category.
                </p>

            </div>


            <form
                method="GET"
                class="filter-form"
            >

                <input
                    type="text"
                    name="search"
                    class="input"
                    placeholder="Search books..."
                    value="<?= htmlspecialchars($search) ?>"
                >


                <select
                    name="category"
                    class="select"
                >

                    <option value="0">
                        All Categories
                    </option>


                    <?php foreach ($categories as $category): ?>

                        <option
                            value="<?= (int)$category["id"] ?>"
                            <?= $category_id == $category["id"]
                                ? "selected"
                                : "" ?>
                        >

                            <?= htmlspecialchars(
                                $category["category_name"]
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    🔍 Search
                </button>


                <a
                    href="books.php"
                    class="btn btn-light"
                >
                    Reset
                </a>

            </form>

        </div>


        <!-- =========================
             BOOK TABLE
        ========================== -->

        <div class="table-card">


            <div class="table-header">

                <h2>
                    Book Collection
                </h2>

                <span>
                    <?= $total_books ?> record(s)
                </span>

            </div>


            <?php if (empty($books)): ?>

                <div class="empty">

                    <div class="empty-icon">
                        🔎
                    </div>

                    <h3>
                        No books found
                    </h3>

                    <p>
                        Try another search term or category.
                    </p>

                </div>

            <?php else: ?>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Book Code
                                </th>

                                <th>
                                    Title
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
                                    Publisher
                                </th>

                                <th>
                                    Year
                                </th>

                                <th>
                                    Shelf
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Available
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($books as $book): ?>

                                <?php

                                $available =
                                    (int)$book["available_quantity"];

                                $quantity =
                                    (int)$book["quantity"];

                                ?>

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

                                        <div class="book-title">

                                            <?= htmlspecialchars(
                                                $book["title"]
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- AUTHOR -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $book["author_name"]
                                            ?? "Unknown"
                                        ) ?>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $book["category_name"]
                                            ?? "Uncategorized"
                                        ) ?>

                                    </td>


                                    <!-- ISBN -->

                                    <td>

                                        <span class="secondary">

                                            <?= !empty($book["isbn"])
                                                ? htmlspecialchars(
                                                    $book["isbn"]
                                                )
                                                : "N/A"
                                            ?>

                                        </span>

                                    </td>


                                    <!-- PUBLISHER -->

                                    <td>

                                        <span class="secondary">

                                            <?= !empty(
                                                $book["publisher"]
                                            )
                                                ? htmlspecialchars(
                                                    $book["publisher"]
                                                )
                                                : "N/A"
                                            ?>

                                        </span>

                                    </td>


                                    <!-- YEAR -->

                                    <td>

                                        <?= !empty(
                                            $book["publication_year"]
                                        )
                                            ? htmlspecialchars(
                                                $book["publication_year"]
                                            )
                                            : "N/A"
                                        ?>

                                    </td>


                                    <!-- SHELF -->

                                    <td>

                                        <?= !empty(
                                            $book["shelf_number"]
                                        )
                                            ? htmlspecialchars(
                                                $book["shelf_number"]
                                            )
                                            : "N/A"
                                        ?>

                                    </td>


                                    <!-- TOTAL -->

                                    <td>

                                        <span class="quantity">

                                            <?= $quantity ?>

                                        </span>

                                    </td>


                                    <!-- AVAILABLE -->

                                    <td>

                                        <span class="quantity">

                                            <?= $available ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php if ($available > 0): ?>

                                            <span
                                                class="status available"
                                            >

                                                <span class="dot"></span>

                                                Available

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="status unavailable"
                                            >

                                                <span class="dot"></span>

                                                Unavailable

                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

</body>

</html>