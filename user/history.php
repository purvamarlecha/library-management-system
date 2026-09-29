<?php

session_start();

require_once "../config/database.php";

/* Student protection */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION["user_role"] !== "student") {
    header("Location: ../admin/dashboard.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"] ?? "Student";

/* Get complete borrowing history */
$stmt = $conn->prepare("
    SELECT
        ib.id,
        b.book_code,
        b.title,
        a.author_name,
        c.category_name,
        ib.issue_date,
        ib.due_date,
        ib.return_date,
        ib.status,
        ib.fine
    FROM issued_books ib
    INNER JOIN books b
        ON ib.book_id = b.id
    LEFT JOIN authors a
        ON b.author_id = a.id
    LEFT JOIN categories c
        ON b.category_id = c.id
    WHERE ib.user_id = ?
    ORDER BY ib.issue_date DESC, ib.id DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$history = [];

while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

$stmt->close();

/* Statistics */
$total_transactions = count($history);
$returned_books = 0;
$current_books = 0;
$total_fine = 0;

foreach ($history as $row) {

    if ($row["status"] === "returned") {
        $returned_books++;
    }

    if ($row["status"] === "issued") {
        $current_books++;
    }

    $total_fine += (float)$row["fine"];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Book History - Library System</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f8fafc;
            color: #111827;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* ================= SIDEBAR ================= */

        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #0f172a, #172554);
            color: white;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            padding: 25px 15px;
            z-index: 1000;
        }

        .logo {
            padding: 0 12px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
            margin-bottom: 20px;
        }

        .logo h2 {
            font-size: 21px;
            margin-bottom: 5px;
        }

        .logo p {
            font-size: 12px;
            color: #94a3b8;
        }

        .menu-title {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 12px;
            margin-bottom: 10px;
        }

        .menu {
            list-style: none;
        }

        .menu li {
            margin-bottom: 6px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #cbd5e1;
            padding: 12px 13px;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.2s;
        }

        .menu a:hover {
            background: rgba(255,255,255,0.08);
            color: white;
        }

        .menu a.active {
            background: #2563eb;
            color: white;
            box-shadow: 0 4px 12px rgba(37,99,235,0.25);
        }

        .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .logout {
            margin-top: 25px;
            border-top: 1px solid rgba(255,255,255,0.12);
            padding-top: 15px;
        }

        .logout a {
            color: #fca5a5;
        }

        .logout a:hover {
            background: rgba(239,68,68,0.12);
            color: #fecaca;
        }

        /* ================= MAIN ================= */

        .main {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
        }

        /* ================= TOPBAR ================= */

        .topbar {
            height: 70px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .page-title h1 {
            font-size: 20px;
            color: #111827;
        }

        .page-title p {
            font-size: 12px;
            color: #6b7280;
            margin-top: 3px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .profile-info strong {
            display: block;
            font-size: 13px;
            color: #111827;
        }

        .profile-info span {
            font-size: 11px;
            color: #6b7280;
        }

        /* ================= CONTENT ================= */

        .content {
            padding: 30px;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h2 {
            font-size: 24px;
            color: #111827;
            margin-bottom: 6px;
        }

        .welcome p {
            font-size: 14px;
            color: #6b7280;
        }

        /* ================= STAT CARDS ================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(15,23,42,0.04);
        }

        .stat-label {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 25px;
            font-weight: bold;
            color: #111827;
        }

        /* ================= TABLE SECTION ================= */

        .table-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(15,23,42,0.04);
        }

        .table-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h2 {
            font-size: 17px;
            color: #111827;
        }

        .table-header p {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        thead {
            background: #f8fafc;
        }

        th {
            text-align: left;
            padding: 13px 15px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        td {
            padding: 14px 15px;
            font-size: 13px;
            color: #374151;
            border-bottom: 1px solid #f1f5f9;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .book-code {
            font-weight: bold;
            color: #2563eb;
        }

        .book-title {
            font-weight: 600;
            color: #111827;
        }

        /* ================= STATUS ================= */

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status.returned {
            background: #dcfce7;
            color: #166534;
        }

        .status.issued {
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* ================= FINE ================= */

        .fine {
            font-weight: 600;
        }

        .fine.zero {
            color: #16a34a;
        }

        .fine.has-fine {
            color: #dc2626;
        }

        /* ================= EMPTY STATE ================= */

        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }

        .empty-icon {
            width: 55px;
            height: 55px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .empty-state h3 {
            font-size: 16px;
            color: #111827;
            margin-bottom: 7px;
        }

        .empty-state p {
            font-size: 13px;
            color: #6b7280;
        }

        /* ================= RESPONSIVE ================= */

        @media (max-width: 1000px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                width: calc(100% - 210px);
            }

            .content {
                padding: 20px;
            }

            .topbar {
                padding: 0 20px;
            }

        }

        @media (max-width: 650px) {

            .layout {
                display: block;
            }

            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .topbar {
                height: auto;
                padding: 15px 20px;
                gap: 15px;
            }

            .profile-info {
                display: none;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 15px;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <div class="logo">
            <h2>Library System</h2>
            <p>Student Portal</p>
        </div>

        <div class="menu-title">
            Main Menu
        </div>

        <ul class="menu">

            <li>
                <a href="dashboard.php">
                    <span class="menu-icon">▣</span>
                    Dashboard
                </a>
            </li>

            <li>
                <a href="books.php">
                    <span class="menu-icon">▤</span>
                    Browse Books
                </a>
            </li>

            <li>
                <a href="issued_books.php">
                    <span class="menu-icon">◫</span>
                    My Issued Books
                </a>
            </li>

            <!-- BOOK HISTORY ACTIVE -->
            <li>
                <a href="history.php" class="active">
                    <span class="menu-icon">◷</span>
                    Book History
                </a>
            </li>

            <li>
                <a href="account.php">
                    <span class="menu-icon">◎</span>
                    My Account
                </a>
            </li>

            <li class="logout">
                <a href="../auth/logout.php">
                    <span class="menu-icon">↪</span>
                    Logout
                </a>
            </li>

        </ul>

    </aside>


    <!-- ================= MAIN ================= -->

    <main class="main">

        <!-- TOPBAR -->

        <div class="topbar">

            <div class="page-title">

                <h1>Book History</h1>

                <p>
                    View your complete borrowing history
                </p>

            </div>

            <div class="profile">

                <div class="avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>

                <div class="profile-info">

                    <strong>
                        <?php echo htmlspecialchars($user_name); ?>
                    </strong>

                    <span>
                        Student
                    </span>

                </div>

            </div>

        </div>


        <!-- CONTENT -->

        <div class="content">

            <div class="welcome">

                <h2>Book History</h2>

                <p>
                    All books that you have borrowed from the library.
                </p>

            </div>


            <!-- STATISTICS -->

            <div class="stats">

                <div class="stat-card">

                    <div class="stat-label">
                        Total Transactions
                    </div>

                    <div class="stat-value">
                        <?php echo $total_transactions; ?>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-label">
                        Returned Books
                    </div>

                    <div class="stat-value">
                        <?php echo $returned_books; ?>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-label">
                        Currently Issued
                    </div>

                    <div class="stat-value">
                        <?php echo $current_books; ?>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-label">
                        Total Fine
                    </div>

                    <div class="stat-value">
                        ₹<?php echo number_format($total_fine, 2); ?>
                    </div>

                </div>

            </div>


            <!-- HISTORY TABLE -->

            <div class="table-card">

                <div class="table-header">

                    <div>

                        <h2>Borrowing History</h2>

                        <p>
                            Complete record of your library transactions
                        </p>

                    </div>

                </div>


                <?php if (count($history) > 0): ?>

                    <div class="table-wrapper">

                        <table>

                            <thead>

                                <tr>

                                    <th>Book Code</th>

                                    <th>Book Title</th>

                                    <th>Author</th>

                                    <th>Category</th>

                                    <th>Issue Date</th>

                                    <th>Due Date</th>

                                    <th>Return Date</th>

                                    <th>Status</th>

                                    <th>Fine</th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($history as $row): ?>

                                    <tr>

                                        <td>

                                            <span class="book-code">
                                                <?php
                                                echo htmlspecialchars(
                                                    $row["book_code"] ?? "-"
                                                );
                                                ?>
                                            </span>

                                        </td>


                                        <td>

                                            <span class="book-title">
                                                <?php
                                                echo htmlspecialchars(
                                                    $row["title"] ?? "-"
                                                );
                                                ?>
                                            </span>

                                        </td>


                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $row["author_name"] ?? "-"
                                            );
                                            ?>
                                        </td>


                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $row["category_name"] ?? "-"
                                            );
                                            ?>
                                        </td>


                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $row["issue_date"] ?? "-"
                                            );
                                            ?>
                                        </td>


                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $row["due_date"] ?? "-"
                                            );
                                            ?>
                                        </td>


                                        <td>

                                            <?php

                                            if (!empty($row["return_date"])) {
                                                echo htmlspecialchars(
                                                    $row["return_date"]
                                                );
                                            } else {
                                                echo "-";
                                            }

                                            ?>

                                        </td>


                                        <td>

                                            <?php if ($row["status"] === "returned"): ?>

                                                <span class="status returned">
                                                    Returned
                                                </span>

                                            <?php else: ?>

                                                <span class="status issued">
                                                    Issued
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?php

                                            $fine = (float)$row["fine"];

                                            if ($fine > 0):

                                            ?>

                                                <span class="fine has-fine">
                                                    ₹<?php echo number_format($fine, 2); ?>
                                                </span>

                                            <?php else: ?>

                                                <span class="fine zero">
                                                    ₹0.00
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            ◷
                        </div>

                        <h3>No Book History</h3>

                        <p>
                            You have not borrowed any books yet.
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

</body>

</html>