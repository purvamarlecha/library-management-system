<?php

session_start();

require_once "../config/database.php";

// Admin protection
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION["user_role"] !== "admin") {
    header("Location: ../user/dashboard.php");
    exit();
}

$message = "";
$error = "";

/* =========================
   ADD AUTHOR
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_author"])) {

    $author_name = trim($_POST["author_name"] ?? "");

    if ($author_name === "") {

        $error = "Please enter an author name.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id FROM authors WHERE author_name = ?"
        );

        $stmt->bind_param("s", $author_name);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = "This author already exists.";

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO authors (author_name)
                 VALUES (?)"
            );

            $stmt->bind_param("s", $author_name);

            if ($stmt->execute()) {
                $message = "Author added successfully.";
            } else {
                $error = "Unable to add author.";
            }
        }

        $stmt->close();
    }
}


/* =========================
   DELETE AUTHOR
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_author"])) {

    $author_id = (int) $_POST["author_id"];

    // Check whether books use this author
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM books
         WHERE author_id = ?"
    );

    $stmt->bind_param("i", $author_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();

    if ($data["total"] > 0) {

        $error = "This author cannot be deleted because books are using this author.";

    } else {

        $stmt = $conn->prepare(
            "DELETE FROM authors WHERE id = ?"
        );

        $stmt->bind_param("i", $author_id);

        if ($stmt->execute()) {
            $message = "Author deleted successfully.";
        } else {
            $error = "Unable to delete author.";
        }

        $stmt->close();
    }
}


/* =========================
   FETCH AUTHORS
========================= */

$authors = $conn->query(
    "SELECT id, author_name
     FROM authors
     ORDER BY author_name ASC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Authors | Library Management System</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #0f172a;
        }

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

        .logo {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 35px;
            padding: 0 10px;
        }

        .logo span {
            color: #60a5fa;
        }

        .menu-title {
            font-size: 12px;
            color: #94a3b8;
            margin: 20px 10px 10px;
            text-transform: uppercase;
        }

        .menu a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 13px 14px;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #2563eb;
            color: white;
        }

        .main {
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .topbar h1 {
            font-size: 28px;
        }

        .profile {
            background: white;
            padding: 10px 16px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.06);
        }

        .card {
            background: white;
            border-radius: 18px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.06);
        }

        .card h2 {
            margin-bottom: 20px;
            font-size: 20px;
        }

        .form-row {
            display: flex;
            gap: 12px;
        }

        input {
            flex: 1;
            padding: 13px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            outline: none;
            font-size: 15px;
        }

        input:focus {
            border-color: #2563eb;
        }

        button {
            border: none;
            background: #2563eb;
            color: white;
            padding: 13px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        button:hover {
            background: #1d4ed8;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .search {
            margin-bottom: 18px;
            max-width: 400px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            color: #475569;
        }

        .delete-btn {
            background: #ef4444;
            padding: 8px 13px;
            border-radius: 8px;
        }

        .delete-btn:hover {
            background: #dc2626;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #64748b;
        }

        @media (max-width: 800px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .form-row {
                flex-direction: column;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

    </style>

</head>

<body>

    <aside class="sidebar">

        <div class="logo">
            📚 <span>Library</span>MS
        </div>

        <div class="menu-title">Main</div>

        <div class="menu">

            <a href="dashboard.php">
                📊 Dashboard
            </a>

            <a href="books.php">
                📚 Books
            </a>

            <a href="categories.php">
                🗂️ Categories
            </a>

            <a href="authors.php" class="active">
                ✍️ Authors
            </a>

            <a href="members.php">
                👥 Members
            </a>

            <a href="issued_books.php">
                🔄 Issue / Return
            </a>

            <a href="qr_books.php">
                📱 QR Books
            </a>

        </div>

        <div class="menu-title">Account</div>

        <div class="menu">

            <a href="../auth/logout.php">
                🚪 Logout
            </a>

        </div>

    </aside>


    <main class="main">

        <div class="topbar">

            <div>
                <h1>Authors</h1>

                <p style="color:#64748b;">
                    Manage book authors
                </p>
            </div>

            <div class="profile">
                👤 <?= htmlspecialchars($_SESSION["user_name"]) ?>
            </div>

        </div>


        <?php if ($message): ?>

            <div class="alert success">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="alert error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <!-- ADD AUTHOR -->

        <div class="card">

            <h2>Add New Author</h2>

            <form method="POST">

                <div class="form-row">

                    <input
                        type="text"
                        name="author_name"
                        placeholder="Enter author name"
                        maxlength="150"
                        required
                    >

                    <button
                        type="submit"
                        name="add_author"
                    >
                        + Add Author
                    </button>

                </div>

            </form>

        </div>


        <!-- AUTHORS -->

        <div class="card">

            <h2>All Authors</h2>

            <input
                type="text"
                id="searchAuthor"
                class="search"
                placeholder="Search authors..."
            >

            <table>

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Author Name</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody id="authorTable">

                    <?php if ($authors->num_rows > 0): ?>

                        <?php $count = 1; ?>

                        <?php while ($author = $authors->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= $count++ ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($author["author_name"]) ?>
                                </td>

                                <td>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Delete this author?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="author_id"
                                            value="<?= $author["id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_author"
                                            class="delete-btn"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="3" class="empty">
                                No authors found.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>


    <script>

        const searchInput =
            document.getElementById("searchAuthor");

        searchInput.addEventListener("keyup", function () {

            const searchValue =
                this.value.toLowerCase();

            const rows =
                document.querySelectorAll("#authorTable tr");

            rows.forEach(function (row) {

                const text =
                    row.innerText.toLowerCase();

                row.style.display =
                    text.includes(searchValue)
                        ? ""
                        : "none";

            });

        });

    </script>

</body>

</html>