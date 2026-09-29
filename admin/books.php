<?php

session_start();

require_once "../config/database.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Only admin can access
if ($_SESSION["user_role"] !== "admin") {
    header("Location: ../user/dashboard.php");
    exit();
}

$message = "";
$messageType = "";

// ======================================================
// ADD BOOK
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_book"])) {

    $book_code = trim($_POST["book_code"] ?? "");
    $title = trim($_POST["title"] ?? "");
    $isbn = trim($_POST["isbn"] ?? "");
    $author_id = intval($_POST["author_id"] ?? 0);
    $category_id = intval($_POST["category_id"] ?? 0);
    $publisher = trim($_POST["publisher"] ?? "");
    $publication_year = intval($_POST["publication_year"] ?? 0);
    $quantity = intval($_POST["quantity"] ?? 0);
    $shelf_number = trim($_POST["shelf_number"] ?? "");

    if (
        empty($book_code) ||
        empty($title) ||
        $author_id <= 0 ||
        $category_id <= 0 ||
        $quantity <= 0
    ) {

        $message = "Please fill all required fields.";
        $messageType = "error";

    } else {

        // Check duplicate book code
        $check = $conn->prepare(
            "SELECT id FROM books WHERE book_code = ?"
        );

        $check->bind_param("s", $book_code);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Book code already exists.";
            $messageType = "error";

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO books
                (
                    book_code,
                    title,
                    isbn,
                    author_id,
                    category_id,
                    publisher,
                    publication_year,
                    quantity,
                    available_quantity,
                    shelf_number
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssiiisiss",
                $book_code,
                $title,
                $isbn,
                $author_id,
                $category_id,
                $publisher,
                $publication_year,
                $quantity,
                $quantity,
                $shelf_number
            );

            if ($stmt->execute()) {

                $message = "Book added successfully.";
                $messageType = "success";

            } else {

                $message = "Unable to add the book.";
                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}


// ======================================================
// DELETE BOOK
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_book"])) {

    $book_id = intval($_POST["book_id"] ?? 0);

    if ($book_id > 0) {

        // Check whether book has currently issued copies
        $check = $conn->prepare(
            "SELECT id
             FROM issued_books
             WHERE book_id = ?
             AND status = 'issued'
             LIMIT 1"
        );

        $check->bind_param("i", $book_id);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "This book cannot be deleted because it is currently issued.";
            $messageType = "error";

        } else {

            $stmt = $conn->prepare(
                "DELETE FROM books WHERE id = ?"
            );

            $stmt->bind_param("i", $book_id);

            if ($stmt->execute()) {

                $message = "Book deleted successfully.";
                $messageType = "success";

            } else {

                $message = "Unable to delete the book.";
                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}


// ======================================================
// EDIT BOOK
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_book"])) {

    $book_id = intval($_POST["edit_book_id"] ?? 0);

    $book_code = trim($_POST["edit_book_code"] ?? "");
    $title = trim($_POST["edit_title"] ?? "");
    $isbn = trim($_POST["edit_isbn"] ?? "");
    $author_id = intval($_POST["edit_author_id"] ?? 0);
    $category_id = intval($_POST["edit_category_id"] ?? 0);
    $publisher = trim($_POST["edit_publisher"] ?? "");
    $publication_year = intval($_POST["edit_publication_year"] ?? 0);
    $quantity = intval($_POST["edit_quantity"] ?? 0);
    $shelf_number = trim($_POST["edit_shelf_number"] ?? "");

    if (
        $book_id <= 0 ||
        empty($book_code) ||
        empty($title) ||
        $author_id <= 0 ||
        $category_id <= 0 ||
        $quantity <= 0
    ) {

        $message = "Please fill all required fields.";
        $messageType = "error";

    } else {

        // Get current book quantity
        $currentStmt = $conn->prepare(
            "SELECT quantity, available_quantity
             FROM books
             WHERE id = ?"
        );

        $currentStmt->bind_param("i", $book_id);
        $currentStmt->execute();

        $currentResult = $currentStmt->get_result();

        if ($currentResult->num_rows === 1) {

            $current = $currentResult->fetch_assoc();

            $oldQuantity = intval($current["quantity"]);
            $oldAvailable = intval($current["available_quantity"]);

            /*
             * Calculate how many copies are currently issued.
             */
            $issuedCopies = $oldQuantity - $oldAvailable;

            /*
             * New quantity cannot be lower than
             * the number of copies currently issued.
             */
            if ($quantity < $issuedCopies) {

                $message =
                    "Quantity cannot be less than the number of currently issued copies.";

                $messageType = "error";

            } else {

                $newAvailable = $quantity - $issuedCopies;

                $stmt = $conn->prepare(
                    "UPDATE books
                     SET
                        book_code = ?,
                        title = ?,
                        isbn = ?,
                        author_id = ?,
                        category_id = ?,
                        publisher = ?,
                        publication_year = ?,
                        quantity = ?,
                        available_quantity = ?,
                        shelf_number = ?
                     WHERE id = ?"
                );

                $stmt->bind_param(
                    "sssiiisissi",
                    $book_code,
                    $title,
                    $isbn,
                    $author_id,
                    $category_id,
                    $publisher,
                    $publication_year,
                    $quantity,
                    $newAvailable,
                    $shelf_number,
                    $book_id
                );

                if ($stmt->execute()) {

                    $message = "Book updated successfully.";
                    $messageType = "success";

                } else {

                    $message = "Unable to update the book.";
                    $messageType = "error";
                }

                $stmt->close();
            }
        }

        $currentStmt->close();
    }
}


// ======================================================
// GET AUTHORS
// ======================================================

$authors = [];

$result = $conn->query(
    "SELECT id, author_name
     FROM authors
     ORDER BY author_name ASC"
);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $authors[] = $row;
    }
}


// ======================================================
// GET CATEGORIES
// ======================================================

$categories = [];

$result = $conn->query(
    "SELECT id, category_name
     FROM categories
     ORDER BY category_name ASC"
);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}


// ======================================================
// GET BOOKS
// ======================================================

$books = [];

$result = $conn->query(
    "SELECT
        b.id,
        b.book_code,
        b.title,
        b.isbn,
        b.author_id,
        b.category_id,
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
     ORDER BY b.id DESC"
);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
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

    <title>Books Management | Smart Library</title>

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
            z-index: 100;
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
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cbd5e1;
            text-decoration: none;
            padding: 13px 12px;
            border-radius: 10px;
            background: rgba(255,255,255,0.07);
            font-size: 14px;
        }

        /* MAIN */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

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

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-heading h2 {
            font-size: 27px;
            margin-bottom: 6px;
        }

        .page-heading p {
            color: #64748b;
            font-size: 14px;
        }

        .primary-btn {
            border: none;
            background: #2563eb;
            color: white;
            padding: 12px 18px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
        }

        .primary-btn:hover {
            background: #1d4ed8;
        }

        /* ALERT */

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert.success {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert.error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* SEARCH */

        .toolbar {
            background: white;
            padding: 18px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .search-box {
            position: relative;
            max-width: 450px;
        }

        .search-box span {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-box input {
            width: 100%;
            height: 45px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 0 15px 0 42px;
            outline: none;
            font-size: 14px;
        }

        .search-box input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.08);
        }

        /* TABLE */

        .table-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            overflow: hidden;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            text-align: left;
            padding: 15px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        td {
            padding: 16px 15px;
            border-top: 1px solid #f1f5f9;
            font-size: 13px;
            vertical-align: middle;
        }

        tr:hover td {
            background: #fafcff;
        }

        .book-title {
            font-weight: 600;
            color: #0f172a;
        }

        .book-code {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge.available {
            background: #dcfce7;
            color: #15803d;
        }

        .badge.unavailable {
            background: #fee2e2;
            color: #b91c1c;
        }

        .actions {
            display: flex;
            gap: 7px;
        }

        .action-btn {
            border: none;
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .edit-btn {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .delete-btn {
            background: #fef2f2;
            color: #dc2626;
        }

        /* MODAL */

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.55);
            z-index: 500;
            padding: 25px;
            overflow-y: auto;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-card {
            background: white;
            width: 100%;
            max-width: 700px;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 25px 80px rgba(15,23,42,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-header h3 {
            font-size: 21px;
        }

        .close-btn {
            border: none;
            background: #f1f5f9;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            color: #475569;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 17px;
        }

        .form-group {
            margin-bottom: 2px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
            color: #334155;
        }

        .form-control {
            width: 100%;
            height: 45px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 0 12px;
            font-size: 13px;
            outline: none;
            background: white;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }

        .secondary-btn {
            border: none;
            background: #f1f5f9;
            color: #334155;
            padding: 11px 17px;
            border-radius: 9px;
            cursor: pointer;
            font-weight: 600;
        }

        /* MOBILE */

        @media (max-width: 1000px) {

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
            .logout span {
                display: none;
            }

            .menu a {
                justify-content: center;
                font-size: 19px;
            }

            .logout {
                left: 10px;
                right: 10px;
            }

            .main {
                margin-left: 70px;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 700px) {

            .topbar {
                padding: 0 18px;
            }

            .profile-info {
                display: none;
            }

            .content {
                padding: 22px 15px;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .modal {
                padding: 12px;
            }

            .modal-card {
                padding: 22px;
            }

        }

    </style>

</head>

<body>


<!-- ======================================================
     SIDEBAR
====================================================== -->

<aside class="sidebar">

    <div class="brand">
        Smart<span>Library</span>
    </div>

    <div class="menu-title">
        Main Menu
    </div>

    <nav class="menu">

        <a href="dashboard.php">
            📊
            <span>Dashboard</span>
        </a>

        <a href="books.php" class="active">
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


<!-- ======================================================
     MAIN
====================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">

        <div>

            <h1>
                Books Management
            </h1>

            <p>
                Manage your library collection
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


        <!-- PAGE HEADING -->

        <div class="page-heading">

            <div>

                <h2>
                    Library Books
                </h2>

                <p>
                    Add, edit, search and manage books.
                </p>

            </div>

            <button
                class="primary-btn"
                onclick="openAddModal()"
            >
                + Add New Book
            </button>

        </div>


        <!-- ALERT -->

        <?php if (!empty($message)): ?>

            <div class="alert <?php echo $messageType; ?>">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <!-- SEARCH -->

        <div class="toolbar">

            <div class="search-box">

                <span>🔍</span>

                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search by title, code, ISBN, author or category..."
                    onkeyup="searchBooks()"
                >

            </div>

        </div>


        <!-- TABLE -->

        <div class="table-card">

            <div class="table-wrapper">

                <table id="booksTable">

                    <thead>

                        <tr>

                            <th>
                                Book Code
                            </th>

                            <th>
                                Book
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
                                Quantity
                            </th>

                            <th>
                                Available
                            </th>

                            <th>
                                Shelf
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($books) > 0): ?>

                        <?php foreach ($books as $book): ?>

                            <tr>

                                <td>

                                    <span class="book-code">

                                        <?php
                                        echo htmlspecialchars(
                                            $book["book_code"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <div class="book-title">

                                        <?php
                                        echo htmlspecialchars(
                                            $book["title"]
                                        );
                                        ?>

                                    </div>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $book["author_name"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $book["category_name"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $book["isbn"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo intval(
                                        $book["quantity"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php

                                    $available =
                                        intval(
                                            $book["available_quantity"]
                                        );

                                    if ($available > 0):

                                    ?>

                                        <span class="badge available">

                                            <?php
                                            echo $available;
                                            ?>
                                            Available

                                        </span>

                                    <?php else: ?>

                                        <span class="badge unavailable">

                                            Not Available

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $book["shelf_number"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <div class="actions">

                                        <button
                                            class="action-btn edit-btn"
                                            onclick='openEditModal(
                                                <?php
                                                echo json_encode(
                                                    $book,
                                                    JSON_HEX_TAG |
                                                    JSON_HEX_APOS |
                                                    JSON_HEX_QUOT |
                                                    JSON_HEX_AMP
                                                );
                                                ?>
                                            )'
                                        >
                                            Edit
                                        </button>


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirmDelete();"
                                        >

                                            <input
                                                type="hidden"
                                                name="book_id"
                                                value="<?php
                                                echo intval(
                                                    $book["id"]
                                                );
                                                ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_book"
                                                class="action-btn delete-btn"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="9"
                                style="
                                    text-align:center;
                                    padding:50px;
                                    color:#64748b;
                                "
                            >

                                📚 No books found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


    </section>

</main>


<!-- ======================================================
     ADD BOOK MODAL
====================================================== -->

<div
    class="modal"
    id="addModal"
>

    <div class="modal-card">

        <div class="modal-header">

            <h3>
                Add New Book
            </h3>

            <button
                class="close-btn"
                onclick="closeModal('addModal')"
            >
                ×
            </button>

        </div>


        <form method="POST">

            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Book Code *
                    </label>

                    <input
                        type="text"
                        name="book_code"
                        class="form-control"
                        placeholder="Example: BOOK006"
                        required
                        maxlength="50"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Book Title *
                    </label>

                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        placeholder="Enter book title"
                        required
                        maxlength="200"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Author *
                    </label>

                    <select
                        name="author_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Select Author
                        </option>

                        <?php foreach ($authors as $author): ?>

                            <option
                                value="<?php
                                echo intval($author["id"]);
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $author["author_name"]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Category *
                    </label>

                    <select
                        name="category_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?php
                                echo intval($category["id"]);
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $category["category_name"]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        ISBN
                    </label>

                    <input
                        type="text"
                        name="isbn"
                        class="form-control"
                        placeholder="ISBN number"
                        maxlength="50"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Publisher
                    </label>

                    <input
                        type="text"
                        name="publisher"
                        class="form-control"
                        placeholder="Publisher name"
                        maxlength="150"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Publication Year
                    </label>

                    <input
                        type="number"
                        name="publication_year"
                        class="form-control"
                        placeholder="Example: 2024"
                        min="1000"
                        max="2100"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Quantity *
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        class="form-control"
                        value="1"
                        min="1"
                        required
                    >

                </div>


                <div class="form-group full">

                    <label>
                        Shelf Number
                    </label>

                    <input
                        type="text"
                        name="shelf_number"
                        class="form-control"
                        placeholder="Example: A-01"
                        maxlength="50"
                    >

                </div>


            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="secondary-btn"
                    onclick="closeModal('addModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="add_book"
                    class="primary-btn"
                >
                    Add Book
                </button>

            </div>

        </form>

    </div>

</div>


<!-- ======================================================
     EDIT BOOK MODAL
====================================================== -->

<div
    class="modal"
    id="editModal"
>

    <div class="modal-card">

        <div class="modal-header">

            <h3>
                Edit Book
            </h3>

            <button
                class="close-btn"
                onclick="closeModal('editModal')"
            >
                ×
            </button>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="edit_book_id"
                id="edit_book_id"
            >


            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Book Code *
                    </label>

                    <input
                        type="text"
                        name="edit_book_code"
                        id="edit_book_code"
                        class="form-control"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Book Title *
                    </label>

                    <input
                        type="text"
                        name="edit_title"
                        id="edit_title"
                        class="form-control"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Author *
                    </label>

                    <select
                        name="edit_author_id"
                        id="edit_author_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Select Author
                        </option>

                        <?php foreach ($authors as $author): ?>

                            <option
                                value="<?php
                                echo intval($author["id"]);
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $author["author_name"]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Category *
                    </label>

                    <select
                        name="edit_category_id"
                        id="edit_category_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?php
                                echo intval($category["id"]);
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $category["category_name"]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        ISBN
                    </label>

                    <input
                        type="text"
                        name="edit_isbn"
                        id="edit_isbn"
                        class="form-control"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Publisher
                    </label>

                    <input
                        type="text"
                        name="edit_publisher"
                        id="edit_publisher"
                        class="form-control"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Publication Year
                    </label>

                    <input
                        type="number"
                        name="edit_publication_year"
                        id="edit_publication_year"
                        class="form-control"
                        min="1000"
                        max="2100"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Quantity *
                    </label>

                    <input
                        type="number"
                        name="edit_quantity"
                        id="edit_quantity"
                        class="form-control"
                        min="1"
                        required
                    >

                </div>


                <div class="form-group full">

                    <label>
                        Shelf Number
                    </label>

                    <input
                        type="text"
                        name="edit_shelf_number"
                        id="edit_shelf_number"
                        class="form-control"
                    >

                </div>


            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="secondary-btn"
                    onclick="closeModal('editModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="edit_book"
                    class="primary-btn"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<script>

// ======================================================
// OPEN ADD MODAL
// ======================================================

function openAddModal() {

    document
        .getElementById("addModal")
        .classList.add("show");
}


// ======================================================
// OPEN EDIT MODAL
// ======================================================

function openEditModal(book) {

    document.getElementById("edit_book_id").value =
        book.id;

    document.getElementById("edit_book_code").value =
        book.book_code;

    document.getElementById("edit_title").value =
        book.title;

    document.getElementById("edit_isbn").value =
        book.isbn || "";

    document.getElementById("edit_author_id").value =
        book.author_id || "";

    document.getElementById("edit_category_id").value =
        book.category_id || "";

    document.getElementById("edit_publisher").value =
        book.publisher || "";

    document.getElementById("edit_publication_year").value =
        book.publication_year || "";

    document.getElementById("edit_quantity").value =
        book.quantity || 1;

    document.getElementById("edit_shelf_number").value =
        book.shelf_number || "";

    document
        .getElementById("editModal")
        .classList.add("show");
}


// ======================================================
// CLOSE MODAL
// ======================================================

function closeModal(id) {

    document
        .getElementById(id)
        .classList.remove("show");
}


// ======================================================
// CLICK OUTSIDE MODAL
// ======================================================

window.addEventListener("click", function(event) {

    if (event.target.classList.contains("modal")) {

        event.target.classList.remove("show");

    }

});


// ======================================================
// SEARCH BOOKS
// ======================================================

function searchBooks() {

    const input =
        document
            .getElementById("searchInput")
            .value
            .toLowerCase();

    const rows =
        document
            .querySelectorAll("#booksTable tbody tr");

    rows.forEach(function(row) {

        const text =
            row.textContent.toLowerCase();

        if (text.includes(input)) {

            row.style.display = "";

        } else {

            row.style.display = "none";

        }

    });
}


// ======================================================
// DELETE CONFIRMATION
// ======================================================

function confirmDelete() {

    return confirm(
        "Are you sure you want to delete this book?"
    );
}

</script>

</body>

</html>