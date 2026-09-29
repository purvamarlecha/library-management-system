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
   DELETE MEMBER
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_member"])) {

    $user_id = (int) $_POST["user_id"];

    // Do not allow admin to delete an admin account
    $stmt = $conn->prepare(
        "SELECT role FROM users WHERE id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    if (!$user) {

        $error = "Member not found.";

    } elseif ($user["role"] === "admin") {

        $error = "Administrator accounts cannot be deleted.";

    } else {

        // Check active issued books
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM issued_books
             WHERE user_id = ?
             AND status = 'issued'"
        );

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $active = $result->fetch_assoc();

        $stmt->close();

        if ($active["total"] > 0) {

            $error = "This member has active issued books and cannot be deleted.";

        } else {

            $stmt = $conn->prepare(
                "DELETE FROM users
                 WHERE id = ?
                 AND role = 'student'"
            );

            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $message = "Member deleted successfully.";
            } else {
                $error = "Unable to delete member.";
            }

            $stmt->close();
        }
    }
}


/* =========================
   FETCH MEMBERS
========================= */

$members = $conn->query(
    "SELECT
        id,
        name,
        email,
        phone,
        created_at
     FROM users
     WHERE role = 'student'
     ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Members | Library Management System</title>

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

        /* MAIN */

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

        .subtitle {
            color: #64748b;
            margin-top: 5px;
        }

        .profile {
            background: white;
            padding: 10px 16px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.06);
        }

        /* CARD */

        .card {
            background: white;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.06);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h2 {
            font-size: 20px;
        }

        /* SEARCH */

        .search {
            width: 350px;
            max-width: 100%;
            padding: 13px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            outline: none;
            font-size: 15px;
        }

        .search:focus {
            border-color: #2563eb;
        }

        /* ALERT */

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

        /* TABLE */

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
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
            font-size: 14px;
        }

        td {
            color: #334155;
        }

        .member-name {
            font-weight: 600;
            color: #0f172a;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
        }

        .delete-btn {
            border: none;
            background: #ef4444;
            color: white;
            padding: 8px 13px;
            border-radius: 8px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background: #dc2626;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #64748b;
        }

        /* MOBILE */

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

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .search {
                width: 100%;
            }

        }

    </style>

</head>

<body>

    <!-- SIDEBAR -->

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

            <a href="authors.php">
                ✍️ Authors
            </a>

            <a href="members.php" class="active">
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


    <!-- MAIN -->

    <main class="main">

        <div class="topbar">

            <div>

                <h1>Members</h1>

                <p class="subtitle">
                    Manage registered library members
                </p>

            </div>

            <div class="profile">
                👤 <?= htmlspecialchars($_SESSION["user_name"]) ?>
            </div>

        </div>


        <!-- ALERTS -->

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


        <!-- MEMBERS CARD -->

        <div class="card">

            <div class="card-header">

                <h2>Registered Members</h2>

                <input
                    type="text"
                    id="memberSearch"
                    class="search"
                    placeholder="Search name, email or phone..."
                >

            </div>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Member</th>

                            <th>Email</th>

                            <th>Phone</th>

                            <th>Joined</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody id="memberTable">

                        <?php if ($members->num_rows > 0): ?>

                            <?php $count = 1; ?>

                            <?php while ($member = $members->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <?= $count++ ?>
                                    </td>

                                    <td class="member-name">
                                        <?= htmlspecialchars($member["name"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($member["email"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($member["phone"] ?: "—") ?>
                                    </td>

                                    <td>
                                        <?= date(
                                            "d M Y",
                                            strtotime($member["created_at"])
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="badge">
                                            Active
                                        </span>
                                    </td>

                                    <td>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this member?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= $member["id"] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_member"
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

                                <td colspan="7" class="empty">

                                    No registered members found.

                                    <br><br>

                                    Students can register using the
                                    <strong>Register</strong> page.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>


    <script>

        const searchInput =
            document.getElementById("memberSearch");

        searchInput.addEventListener("keyup", function () {

            const value =
                this.value.toLowerCase();

            const rows =
                document.querySelectorAll("#memberTable tr");

            rows.forEach(function (row) {

                const text =
                    row.innerText.toLowerCase();

                row.style.display =
                    text.includes(value)
                        ? ""
                        : "none";

            });

        });

    </script>

</body>

</html>