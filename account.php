<?php
session_start();
require_once "../config/database.php";

/* =========================================================
   STUDENT ACCESS PROTECTION
   ========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION["user_role"] !== "student") {
    header("Location: ../admin/dashboard.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* =========================================================
   GET STUDENT ACCOUNT DETAILS
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        email,
        phone,
        role,
        created_at
    FROM users
    WHERE id = ?
");

if (!$stmt) {
    die("Database query failed.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    session_unset();
    session_destroy();

    header("Location: ../auth/login.php");
    exit();
}

$user = $result->fetch_assoc();

$stmt->close();

/* =========================================================
   PROFILE INITIAL
   ========================================================= */

$initial = strtoupper(
    substr(trim($user["name"]), 0, 1)
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

    <title>
        My Account - Library Management System
    </title>

    <style>

        /* =====================================================
           GLOBAL
           ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f4f7fb;
            color: #1f2937;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }


        /* =====================================================
           SIDEBAR
           SAME STUDENT DASHBOARD FORMAT
           ===================================================== */

        .sidebar {
            width: 250px;
            background: linear-gradient(
                180deg,
                #0f172a,
                #172554
            );
            color: white;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            padding: 25px 15px;
            z-index: 1000;
        }


        /* LOGO */

        .logo {
            padding: 0 12px 25px;
            border-bottom:
                1px solid rgba(255,255,255,0.12);
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


        /* MENU TITLE */

        .menu-title {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 12px;
            margin-bottom: 10px;
        }


        /* MENU */

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
            background:
                rgba(255,255,255,0.08);
            color: white;
        }

        .menu a.active {
            background: #2563eb;
            color: white;
            box-shadow:
                0 4px 12px
                rgba(37,99,235,0.25);
        }


        /* MENU ICON */

        .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }


        /* LOGOUT */

        .logout {
            margin-top: 25px;
            border-top:
                1px solid rgba(255,255,255,0.12);
            padding-top: 15px;
        }

        .logout a {
            color: #fca5a5;
        }

        .logout a:hover {
            background:
                rgba(239,68,68,0.12);
            color: #fecaca;
        }


        /* =====================================================
           MAIN
           ===================================================== */

        .main {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
        }


        /* =====================================================
           TOPBAR
           ===================================================== */

        .topbar {
            height: 70px;
            background: white;
            border-bottom:
                1px solid #e5e7eb;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 30px;
        }


        /* PAGE TITLE */

        .page-title h1 {
            font-size: 20px;
            color: #111827;
        }

        .page-title p {
            font-size: 12px;
            color: #6b7280;
            margin-top: 3px;
        }


        /* PROFILE */

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


        /* =====================================================
           CONTENT
           ===================================================== */

        .content {
            padding: 30px;
        }


        /* =====================================================
           BLUE WELCOME SECTION
           ===================================================== */

        .welcome {
            background: linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            );

            color: white;
            border-radius: 12px;

            padding: 25px 28px;
            margin-bottom: 25px;
        }

        .welcome h2 {
            font-size: 22px;
            margin-bottom: 6px;
        }

        .welcome p {
            color: #dbeafe;
            font-size: 13px;
        }


        /* =====================================================
           ACCOUNT CARD
           ===================================================== */

        .account-card {
            background: white;

            border:
                1px solid #e5e7eb;

            border-radius: 10px;

            overflow: hidden;

            box-shadow:
                0 2px 6px
                rgba(15,23,42,0.04);
        }


        /* ACCOUNT HEADER */

        .account-header {
            padding: 22px 25px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;
            align-items: center;
            gap: 15px;
        }


        /* LARGE AVATAR */

        .large-avatar {
            width: 58px;
            height: 58px;

            border-radius: 50%;

            background: #2563eb;
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
            font-weight: bold;

            flex-shrink: 0;
        }

        .account-header h2 {
            font-size: 18px;
            color: #111827;
            margin-bottom: 4px;
        }

        .account-header p {
            font-size: 12px;
            color: #6b7280;
        }


        /* =====================================================
           ACCOUNT TABLE
           ===================================================== */

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            width: 230px;

            background: #f8fafc;

            color: #475569;

            font-size: 12px;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            padding: 17px 22px;

            text-align: left;

            border-bottom:
                1px solid #e5e7eb;
        }

        td {
            padding: 17px 22px;

            font-size: 14px;

            color: #374151;

            border-bottom:
                1px solid #eef2f7;
        }

        tr:last-child th,
        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8fafc;
        }


        /* VALUES */

        .value {
            font-weight: 500;
            color: #111827;
        }

        .student-id {
            color: #2563eb;
            font-weight: bold;
        }


        /* ROLE */

        .role {
            display: inline-block;

            background: #dbeafe;

            color: #1d4ed8;

            padding: 6px 11px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

            text-transform: uppercase;
        }


        /* DATE */

        .date {
            color: #475569;
        }


        /* =====================================================
           ACCOUNT INFORMATION BOX
           ===================================================== */

        .info-box {
            margin-top: 20px;

            background: #eff6ff;

            border:
                1px solid #bfdbfe;

            border-radius: 10px;

            padding: 18px 20px;
        }

        .info-box h3 {
            color: #1e40af;

            font-size: 14px;

            margin-bottom: 6px;
        }

        .info-box p {
            color: #475569;

            font-size: 12px;

            line-height: 1.6;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

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

            th {
                width: 160px;
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


        <!-- LOGO -->

        <div class="logo">

            <h2>
                Library System
            </h2>

            <p>
                Student Portal
            </p>

        </div>


        <!-- MENU TITLE -->

        <div class="menu-title">
            Main Menu
        </div>


        <!-- MENU -->

        <ul class="menu">


            <!-- DASHBOARD -->

            <li>

                <a href="dashboard.php">

                    <span class="menu-icon">
                        ▣
                    </span>

                    Dashboard

                </a>

            </li>


            <!-- BROWSE BOOKS -->

            <li>

                <a href="books.php">

                    <span class="menu-icon">
                        ▤
                    </span>

                    Browse Books

                </a>

            </li>


            <!-- ISSUED BOOKS -->

            <li>

                <a href="issued_books.php">

                    <span class="menu-icon">
                        ◫
                    </span>

                    My Issued Books

                </a>

            </li>


            <!-- BOOK HISTORY -->

            <li>

                <a href="history.php">

                    <span class="menu-icon">
                        ◷
                    </span>

                    Book History

                </a>

            </li>


            <!-- MY ACCOUNT -->

            <li>

                <a
                    href="account.php"
                    class="active"
                >

                    <span class="menu-icon">
                        ◎
                    </span>

                    My Account

                </a>

            </li>


            <!-- LOGOUT -->

            <li class="logout">

                <a href="../auth/logout.php">

                    <span class="menu-icon">
                        ↪
                    </span>

                    Logout

                </a>

            </li>


        </ul>

    </aside>



    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="main">


        <!-- =================================================
             TOPBAR
             ================================================= -->

        <header class="topbar">


            <!-- PAGE TITLE -->

            <div class="page-title">

                <h1>
                    My Account
                </h1>

                <p>
                    Student account information
                </p>

            </div>


            <!-- PROFILE -->

            <div class="profile">


                <div class="avatar">

                    <?php
                    echo htmlspecialchars($initial);
                    ?>

                </div>


                <div class="profile-info">

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $user["name"]
                        );
                        ?>

                    </strong>

                    <span>
                        Student
                    </span>

                </div>


            </div>

        </header>



        <!-- =================================================
             PAGE CONTENT
             ================================================= -->

        <section class="content">


            <!-- WELCOME -->

            <div class="welcome">

                <h2>
                    Account Details
                </h2>

                <p>
                    View your registered information
                    in the Library Management System.
                </p>

            </div>



            <!-- =================================================
                 ACCOUNT CARD
                 ================================================= -->

            <div class="account-card">


                <!-- ACCOUNT HEADER -->

                <div class="account-header">


                    <div class="large-avatar">

                        <?php
                        echo htmlspecialchars($initial);
                        ?>

                    </div>


                    <div>

                        <h2>

                            <?php
                            echo htmlspecialchars(
                                $user["name"]
                            );
                            ?>

                        </h2>

                        <p>
                            Registered Library Student
                        </p>

                    </div>


                </div>



                <!-- ACCOUNT TABLE -->

                <div class="table-wrapper">

                    <table>

                        <tbody>


                            <!-- STUDENT ID -->

                            <tr>

                                <th>
                                    Student ID
                                </th>

                                <td>

                                    <span class="student-id">

                                        #

                                        <?php
                                        echo htmlspecialchars(
                                            $user["id"]
                                        );
                                        ?>

                                    </span>

                                </td>

                            </tr>



                            <!-- FULL NAME -->

                            <tr>

                                <th>
                                    Full Name
                                </th>

                                <td class="value">

                                    <?php
                                    echo htmlspecialchars(
                                        $user["name"]
                                    );
                                    ?>

                                </td>

                            </tr>



                            <!-- EMAIL -->

                            <tr>

                                <th>
                                    Email Address
                                </th>

                                <td class="value">

                                    <?php
                                    echo htmlspecialchars(
                                        $user["email"]
                                    );
                                    ?>

                                </td>

                            </tr>



                            <!-- PHONE -->

                            <tr>

                                <th>
                                    Phone Number
                                </th>

                                <td class="value">

                                    <?php

                                    if (
                                        !empty(
                                            $user["phone"]
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            $user["phone"]
                                        );

                                    } else {

                                        echo "Not provided";

                                    }

                                    ?>

                                </td>

                            </tr>



                            <!-- ROLE -->

                            <tr>

                                <th>
                                    Account Role
                                </th>

                                <td>

                                    <span class="role">

                                        <?php
                                        echo htmlspecialchars(
                                            $user["role"]
                                        );
                                        ?>

                                    </span>

                                </td>

                            </tr>



                            <!-- REGISTRATION DATE -->

                            <tr>

                                <th>
                                    Registration Date
                                </th>

                                <td class="date">

                                    <?php

                                    if (
                                        !empty(
                                            $user["created_at"]
                                        )
                                    ) {

                                        echo date(
                                            "d M Y, h:i A",
                                            strtotime(
                                                $user["created_at"]
                                            )
                                        );

                                    } else {

                                        echo "Not available";

                                    }

                                    ?>

                                </td>

                            </tr>


                        </tbody>

                    </table>

                </div>


            </div>



            <!-- =================================================
                 INFORMATION BOX
                 ================================================= -->

            <div class="info-box">

                <h3>
                    Account Information
                </h3>

                <p>
                    Your account information is maintained
                    by the Library Management System. Contact
                    the library administrator if any registered
                    information needs to be corrected.
                </p>

            </div>


        </section>

    </main>

</div>

</body>

</html>