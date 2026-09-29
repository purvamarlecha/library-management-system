<?php

session_start();

require_once "../config/database.php";

/* =========================
   ADMIN PROTECTION
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION["user_role"] !== "admin") {
    header("Location: ../user/dashboard.php");
    exit();
}


/* =========================
   VARIABLES
========================= */

$message = "";
$error = "";

/*
 * IMPORTANT:
 * This receives the book ID from QR scanner.
 *
 * Example:
 * issued_books.php?book_id=5
 */
$selected_book_id = intval($_GET["book_id"] ?? 0);


/* =========================
   ISSUE BOOK
========================= */

if (isset($_POST["issue_book"])) {

    $book_id = intval($_POST["book_id"] ?? 0);
    $user_id = intval($_POST["user_id"] ?? 0);

    $issue_date = $_POST["issue_date"] ?? "";
    $due_date = $_POST["due_date"] ?? "";


    /* Keep selected book after form submission */
    $selected_book_id = $book_id;


    if (
        $book_id <= 0 ||
        $user_id <= 0 ||
        empty($issue_date) ||
        empty($due_date)
    ) {

        $error = "Please fill all required fields.";

    } elseif ($due_date < $issue_date) {

        $error = "Due date cannot be before issue date.";

    } else {

        /* =========================
           CHECK BOOK AVAILABILITY
        ========================= */

        $stmt = $conn->prepare(
            "SELECT title, available_quantity
             FROM books
             WHERE id = ?"
        );

        $stmt->bind_param("i", $book_id);
        $stmt->execute();

        $book_result = $stmt->get_result();
        $book = $book_result->fetch_assoc();

        $stmt->close();


        if (!$book) {

            $error = "Book not found.";

        } elseif ($book["available_quantity"] <= 0) {

            $error = "This book is currently unavailable.";

        } else {

            /* =========================
               CHECK DUPLICATE ACTIVE ISSUE
            ========================= */

            $stmt = $conn->prepare(
                "SELECT id
                 FROM issued_books
                 WHERE book_id = ?
                 AND user_id = ?
                 AND status = 'issued'"
            );

            $stmt->bind_param("ii", $book_id, $user_id);
            $stmt->execute();

            $existing = $stmt->get_result();


            if ($existing->num_rows > 0) {

                $error = "This student already has this book.";

                $stmt->close();

            } else {

                $stmt->close();


                /* =========================
                   INSERT ISSUE RECORD
                ========================= */

                $stmt = $conn->prepare(
                    "INSERT INTO issued_books
                    (
                        book_id,
                        user_id,
                        issue_date,
                        due_date,
                        status,
                        fine
                    )
                    VALUES (?, ?, ?, ?, 'issued', 0.00)"
                );

                $stmt->bind_param(
                    "iiss",
                    $book_id,
                    $user_id,
                    $issue_date,
                    $due_date
                );


                if ($stmt->execute()) {

                    /* =========================
                       DECREASE AVAILABLE QUANTITY
                    ========================= */

                    $update = $conn->prepare(
                        "UPDATE books
                         SET available_quantity = available_quantity - 1
                         WHERE id = ?
                         AND available_quantity > 0"
                    );

                    $update->bind_param("i", $book_id);
                    $update->execute();
                    $update->close();


                    $message = "Book issued successfully.";

                    /*
                     * Clear selected book after successful issue.
                     */
                    $selected_book_id = 0;

                } else {

                    $error = "Unable to issue book.";

                }

                $stmt->close();
            }
        }
    }
}


/* =========================
   RETURN BOOK
========================= */

if (isset($_POST["return_book"])) {

    $issue_id = intval($_POST["issue_id"] ?? 0);


    /* =========================
       GET ISSUE INFORMATION
    ========================= */

    $stmt = $conn->prepare(
        "SELECT book_id, due_date
         FROM issued_books
         WHERE id = ?
         AND status = 'issued'"
    );

    $stmt->bind_param("i", $issue_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $issue = $result->fetch_assoc();

    $stmt->close();


    if (!$issue) {

        $error = "Issued book record not found.";

    } else {

        $return_date = date("Y-m-d");


        /* =========================
           FINE CALCULATION
           
           ₹10 per late day
        ========================= */

        $due_timestamp = strtotime($issue["due_date"]);
        $return_timestamp = strtotime($return_date);

        $fine = 0;


        if ($return_timestamp > $due_timestamp) {

            $late_days = floor(
                ($return_timestamp - $due_timestamp) / 86400
            );

            $fine = $late_days * 10;
        }


        /* =========================
           UPDATE ISSUE RECORD
        ========================= */

        $stmt = $conn->prepare(
            "UPDATE issued_books
             SET
                 return_date = ?,
                 status = 'returned',
                 fine = ?
             WHERE id = ?
             AND status = 'issued'"
        );

        $stmt->bind_param(
            "sdi",
            $return_date,
            $fine,
            $issue_id
        );


        if ($stmt->execute() && $stmt->affected_rows > 0) {

            /* =========================
               INCREASE AVAILABLE QUANTITY
            ========================= */

            $update = $conn->prepare(
                "UPDATE books
                 SET available_quantity = available_quantity + 1
                 WHERE id = ?"
            );

            $update->bind_param("i", $issue["book_id"]);
            $update->execute();
            $update->close();


            $message =
                "Book returned successfully. Fine: ₹" .
                number_format($fine, 2);

        } else {

            $error = "Unable to return book.";

        }

        $stmt->close();
    }
}


/* =========================
   GET AVAILABLE BOOKS
========================= */

$books = $conn->query(
    "SELECT
        id,
        title,
        book_code,
        available_quantity
     FROM books
     WHERE available_quantity > 0
     ORDER BY title ASC"
);


/* =========================
   GET STUDENTS
========================= */

$students = $conn->query(
    "SELECT
        id,
        name,
        email
     FROM users
     WHERE role = 'student'
     ORDER BY name ASC"
);


/* =========================
   GET ISSUE / RETURN HISTORY
========================= */

$issued_books = $conn->query(
    "SELECT
        issued_books.id,
        books.title,
        books.book_code,
        users.name AS student_name,
        users.email,
        issued_books.issue_date,
        issued_books.due_date,
        issued_books.return_date,
        issued_books.status,
        issued_books.fine
     FROM issued_books
     INNER JOIN books
        ON issued_books.book_id = books.id
     INNER JOIN users
        ON issued_books.user_id = users.id
     ORDER BY issued_books.id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Issue & Return Books | Library Management</title>


<style>

/* =========================
   RESET
========================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


/* =========================
   BODY
========================= */

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f8fafc;
    color: #0f172a;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;

    width: 245px;
    height: 100vh;

    background: #0f172a;
    color: white;

    padding: 25px 16px;
}


.logo {
    font-size: 22px;
    font-weight: bold;

    padding: 0 12px 30px;
}


.logo span {
    color: #3b82f6;
}


.nav a {
    display: block;

    color: #cbd5e1;
    text-decoration: none;

    padding: 13px 15px;

    border-radius: 10px;

    margin-bottom: 6px;

    transition: 0.2s;
}


.nav a:hover,
.nav a.active {
    background: #2563eb;
    color: white;
}


/* =========================
   MAIN
========================= */

.main {
    margin-left: 245px;
    padding: 30px;
}


.header {
    margin-bottom: 25px;
}


.header h1 {
    font-size: 30px;
    margin-bottom: 7px;
}


.header p {
    color: #64748b;
}


/* =========================
   MESSAGES
========================= */

.message {
    background: #dcfce7;
    color: #166534;

    padding: 14px 18px;

    border-radius: 10px;

    margin-bottom: 20px;
}


.error {
    background: #fee2e2;
    color: #991b1b;

    padding: 14px 18px;

    border-radius: 10px;

    margin-bottom: 20px;
}


/* =========================
   CARDS
========================= */

.card {
    background: white;

    border-radius: 18px;

    padding: 25px;

    box-shadow:
        0 5px 20px rgba(15, 23, 42, 0.06);

    margin-bottom: 25px;
}


.card h2 {
    margin-bottom: 20px;

    font-size: 20px;
}


/* =========================
   FORM
========================= */

.form-grid {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 18px;
}


.form-group label {
    display: block;

    margin-bottom: 7px;

    font-weight: 600;

    font-size: 14px;
}


.form-group select,
.form-group input {
    width: 100%;

    padding: 12px 14px;

    border: 1px solid #cbd5e1;

    border-radius: 9px;

    font-size: 14px;

    outline: none;

    background: white;
}


.form-group select:focus,
.form-group input:focus {
    border-color: #2563eb;

    box-shadow:
        0 0 0 3px rgba(37, 99, 235, 0.10);
}


/* =========================
   BUTTON
========================= */

.button {
    margin-top: 20px;

    padding: 12px 20px;

    border: none;

    border-radius: 9px;

    background: #2563eb;

    color: white;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.2s;
}


.button:hover {
    background: #1d4ed8;

    transform: translateY(-1px);
}


/* =========================
   TABLE
========================= */

.table-wrapper {
    overflow-x: auto;
}


table {
    width: 100%;

    border-collapse: collapse;
}


th {
    background: #f1f5f9;

    text-align: left;

    padding: 14px;

    font-size: 13px;

    color: #475569;

    white-space: nowrap;
}


td {
    padding: 14px;

    border-bottom:
        1px solid #e2e8f0;

    font-size: 14px;

    vertical-align: middle;
}


td strong {
    color: #0f172a;
}


td small {
    color: #64748b;
}


/* =========================
   STATUS
========================= */

.status-issued {
    background: #dbeafe;

    color: #1d4ed8;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}


.status-returned {
    background: #dcfce7;

    color: #166534;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}


/* =========================
   RETURN BUTTON
========================= */

.return-btn {
    border: none;

    background: #16a34a;

    color: white;

    padding: 8px 12px;

    border-radius: 7px;

    cursor: pointer;

    font-size: 12px;

    font-weight: bold;
}


.return-btn:hover {
    background: #15803d;
}


/* =========================
   EMPTY TABLE
========================= */

.no-data {
    text-align: center;

    padding: 30px;

    color: #64748b;
}


/* =========================
   MOBILE
========================= */

@media (max-width: 800px) {

    .sidebar {
        position: static;

        width: 100%;

        height: auto;

        padding: 18px 12px;
    }


    .logo {
        padding-bottom: 18px;
    }


    .nav {
        display: grid;

        grid-template-columns:
            repeat(2, 1fr);

        gap: 6px;
    }


    .nav a {
        margin-bottom: 0;
    }


    .main {
        margin-left: 0;

        padding: 20px;
    }


    .form-grid {
        grid-template-columns: 1fr;
    }


    .header h1 {
        font-size: 25px;
    }


    .card {
        padding: 18px;
    }
}


@media (max-width: 500px) {

    .nav {
        grid-template-columns: 1fr;
    }


    .main {
        padding: 14px;
    }
}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">
        📚 <span>Library</span> Admin
    </div>


    <nav class="nav">

        <a href="dashboard.php">
            Dashboard
        </a>


        <a href="books.php">
            Books
        </a>


        <a href="categories.php">
            Categories
        </a>


        <a href="authors.php">
            Authors
        </a>


        <a href="members.php">
            Members
        </a>


        <a
            href="issued_books.php"
            class="active"
        >
            Issue / Return
        </a>


        <a href="qr_books.php">
            QR Books
        </a>


        <a href="qr_scanner.php">
            QR Scanner
        </a>


        <a href="../auth/logout.php">
            Logout
        </a>

    </nav>

</aside>



<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">


    <div class="header">

        <h1>
            Issue & Return Books
        </h1>

        <p>
            Manage book circulation, returns and late fines.
        </p>

    </div>



    <!-- =========================
         SUCCESS MESSAGE
    ========================= -->

    <?php if (!empty($message)): ?>

        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>



    <!-- =========================
         ERROR MESSAGE
    ========================= -->

    <?php if (!empty($error)): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>



    <!-- =========================
         ISSUE BOOK
    ========================= -->

    <div class="card">

        <h2>
            📖 Issue a Book
        </h2>


        <form method="POST">


            <div class="form-grid">


                <!-- BOOK -->

                <div class="form-group">

                    <label>
                        Book
                    </label>


                    <select
                        name="book_id"
                        required
                    >

                        <option value="">
                            Select a book
                        </option>


                        <?php while ($book = $books->fetch_assoc()): ?>

                            <option
                                value="<?= (int)$book["id"] ?>"
                                <?= (
                                    $selected_book_id ==
                                    $book["id"]
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $book["title"]
                                ) ?>

                                —

                                <?= htmlspecialchars(
                                    $book["book_code"]
                                ) ?>

                                (
                                <?= (int)$book["available_quantity"] ?>
                                available
                                )

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>



                <!-- STUDENT -->

                <div class="form-group">

                    <label>
                        Student
                    </label>


                    <select
                        name="user_id"
                        required
                    >

                        <option value="">
                            Select student
                        </option>


                        <?php while ($student = $students->fetch_assoc()): ?>

                            <option
                                value="<?= (int)$student["id"] ?>"
                            >

                                <?= htmlspecialchars(
                                    $student["name"]
                                ) ?>

                                —

                                <?= htmlspecialchars(
                                    $student["email"]
                                ) ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>



                <!-- ISSUE DATE -->

                <div class="form-group">

                    <label>
                        Issue Date
                    </label>


                    <input
                        type="date"
                        name="issue_date"
                        value="<?= date("Y-m-d") ?>"
                        required
                    >

                </div>



                <!-- DUE DATE -->

                <div class="form-group">

                    <label>
                        Due Date
                    </label>


                    <input
                        type="date"
                        name="due_date"
                        min="<?= date("Y-m-d") ?>"
                        required
                    >

                </div>

            </div>



            <button
                type="submit"
                name="issue_book"
                class="button"
            >
                Issue Book
            </button>

        </form>

    </div>



    <!-- =========================
         ISSUE HISTORY
    ========================= -->

    <div class="card">

        <h2>
            📋 Issue / Return History
        </h2>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Book
                        </th>

                        <th>
                            Student
                        </th>

                        <th>
                            Issue Date
                        </th>

                        <th>
                            Due Date
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Fine
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($issued_books && $issued_books->num_rows > 0): ?>


                    <?php while ($row = $issued_books->fetch_assoc()): ?>


                        <tr>


                            <!-- BOOK -->

                            <td>

                                <strong>
                                    <?= htmlspecialchars(
                                        $row["title"]
                                    ) ?>
                                </strong>

                                <br>

                                <small>
                                    <?= htmlspecialchars(
                                        $row["book_code"]
                                    ) ?>
                                </small>

                            </td>



                            <!-- STUDENT -->

                            <td>

                                <?= htmlspecialchars(
                                    $row["student_name"]
                                ) ?>

                                <br>

                                <small>
                                    <?= htmlspecialchars(
                                        $row["email"]
                                    ) ?>
                                </small>

                            </td>



                            <!-- ISSUE DATE -->

                            <td>
                                <?= htmlspecialchars(
                                    $row["issue_date"]
                                ) ?>
                            </td>



                            <!-- DUE DATE -->

                            <td>
                                <?= htmlspecialchars(
                                    $row["due_date"]
                                ) ?>
                            </td>



                            <!-- STATUS -->

                            <td>

                                <?php if (
                                    $row["status"] === "issued"
                                ): ?>

                                    <span class="status-issued">
                                        Issued
                                    </span>

                                <?php else: ?>

                                    <span class="status-returned">
                                        Returned
                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- FINE -->

                            <td>

                                ₹<?= number_format(
                                    (float)$row["fine"],
                                    2
                                ) ?>

                            </td>



                            <!-- ACTION -->

                            <td>

                                <?php if (
                                    $row["status"] === "issued"
                                ): ?>


                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="issue_id"
                                            value="<?= (int)$row["id"] ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="return_book"
                                            class="return-btn"
                                            onclick="return confirm('Are you sure you want to return this book?');"
                                        >
                                            Return
                                        </button>

                                    </form>


                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="7"
                            class="no-data"
                        >
                            No books have been issued yet.
                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>


</main>


</body>

</html>