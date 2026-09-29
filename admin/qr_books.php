<?php

session_start();

require_once "../config/database.php";

/* Admin protection */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION["user_role"] !== "admin") {
    header("Location: ../user/dashboard.php");
    exit();
}

/* Get all books */
$books = $conn->query(
    "SELECT
        id,
        book_code,
        title,
        isbn,
        shelf_number,
        quantity,
        available_quantity
     FROM books
     ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>QR Books | Library Management</title>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

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

/* Sidebar */

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
}

.nav a:hover,
.nav a.active {
    background: #2563eb;
    color: white;
}

/* Main */

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

/* Info */

.info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
    padding: 16px 18px;
    border-radius: 12px;
    margin-bottom: 25px;
}

/* Grid */

.book-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 22px;
}

/* Book Card */

.book-card {
    background: white;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 5px 20px rgba(15, 23, 42, 0.07);
    text-align: center;
}

.book-card h2 {
    font-size: 18px;
    margin-bottom: 8px;
}

.book-code {
    color: #2563eb;
    font-weight: bold;
    font-size: 13px;
    margin-bottom: 18px;
}

.qr-box {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 15px 0 20px;
    min-height: 180px;
}

.qr-box canvas,
.qr-box img {
    border: 8px solid white;
}

.book-info {
    text-align: left;
    background: #f8fafc;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 15px;
}

.book-info p {
    margin: 6px 0;
    font-size: 13px;
    color: #475569;
}

.book-info strong {
    color: #0f172a;
}

.download-btn {
    border: none;
    background: #2563eb;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    font-size: 13px;
}

.download-btn:hover {
    background: #1d4ed8;
}

.no-books {
    background: white;
    padding: 40px;
    text-align: center;
    border-radius: 18px;
    color: #64748b;
}

/* Responsive */

@media (max-width: 800px) {

    .sidebar {
        position: static;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }

    .book-grid {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<!-- SIDEBAR -->

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

        <a href="issued_books.php">
            Issue / Return
        </a>

        <a href="qr_books.php" class="active">
            QR Books
        </a>

        <a href="../auth/logout.php">
            Logout
        </a>

    </nav>

</aside>


<!-- MAIN -->

<main class="main">

    <div class="header">

        <h1>QR Book Management</h1>

        <p>
            Generate and download QR codes for your library books.
        </p>

    </div>


    <div class="info">

        <strong>How it works:</strong>

        Each QR code contains the unique
        <strong>Book Code</strong>.
        Later, the QR scanner will use this code to find the book
        automatically.

    </div>


    <?php if ($books && $books->num_rows > 0): ?>

        <div class="book-grid">

            <?php while ($book = $books->fetch_assoc()): ?>

                <div class="book-card">

                    <h2>
                        <?= htmlspecialchars($book["title"]) ?>
                    </h2>

                    <div class="book-code">

                        <?= htmlspecialchars($book["book_code"]) ?>

                    </div>


                    <!-- QR -->

                    <div
                        class="qr-box"
                        id="qr-<?= $book["id"] ?>"
                    ></div>


                    <!-- Book information -->

                    <div class="book-info">

                        <p>
                            <strong>ISBN:</strong>
                            <?= htmlspecialchars($book["isbn"] ?: "N/A") ?>
                        </p>

                        <p>
                            <strong>Shelf:</strong>
                            <?= htmlspecialchars($book["shelf_number"] ?: "N/A") ?>
                        </p>

                        <p>
                            <strong>Total:</strong>
                            <?= htmlspecialchars($book["quantity"]) ?>
                        </p>

                        <p>
                            <strong>Available:</strong>
                            <?= htmlspecialchars($book["available_quantity"]) ?>
                        </p>

                    </div>


                    <button
                        class="download-btn"
                        onclick="downloadQR(
                            'qr-<?= $book["id"] ?>',
                            '<?= htmlspecialchars($book["book_code"], ENT_QUOTES) ?>'
                        )"
                    >
                        Download QR
                    </button>

                </div>


                <script>

                new QRCode(
                    document.getElementById(
                        "qr-<?= $book["id"] ?>"
                    ),
                    {
                        text: "<?= htmlspecialchars($book["book_code"], ENT_QUOTES) ?>",
                        width: 170,
                        height: 170,
                        correctLevel: QRCode.CorrectLevel.H
                    }
                );

                </script>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="no-books">

            <h2>No books found</h2>

            <p>
                Add books first from the Books section.
            </p>

        </div>

    <?php endif; ?>

</main>


<script>

function downloadQR(elementId, bookCode) {

    const container = document.getElementById(elementId);

    const canvas = container.querySelector("canvas");

    if (!canvas) {

        alert("QR code is not ready yet.");

        return;
    }

    const link = document.createElement("a");

    link.download = bookCode + "-QR.png";

    link.href = canvas.toDataURL("image/png");

    link.click();

}

</script>

</body>

</html>