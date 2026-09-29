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

$book = null;
$error = "";

$scanned_code = trim($_GET["book_code"] ?? "");


/* =========================
   FIND BOOK FROM QR CODE
========================= */

if (!empty($scanned_code)) {

    $stmt = $conn->prepare(
        "SELECT
            books.id,
            books.book_code,
            books.title,
            books.isbn,
            books.publisher,
            books.publication_year,
            books.quantity,
            books.available_quantity,
            books.shelf_number,
            authors.author_name,
            categories.category_name
         FROM books
         LEFT JOIN authors
            ON books.author_id = authors.id
         LEFT JOIN categories
            ON books.category_id = categories.id
         WHERE books.book_code = ?"
    );

    $stmt->bind_param("s", $scanned_code);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $book = $result->fetch_assoc();

    } else {

        $error =
            "No book was found with code: " .
            $scanned_code;
    }

    $stmt->close();
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

<title>QR Scanner | Library Management</title>


<!-- =========================
     HTML5 QR CODE LIBRARY
========================= -->

<script
    src="https://unpkg.com/html5-qrcode"
    type="text/javascript">
</script>


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
    font-family:
        Arial,
        Helvetica,
        sans-serif;

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

    overflow-y: auto;
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

    min-height: 100vh;
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

    line-height: 1.5;
}


/* =========================
   ERROR
========================= */

.error {
    max-width: 700px;

    margin: 0 auto 25px;

    background: #fee2e2;

    color: #991b1b;

    border: 1px solid #fecaca;

    padding: 15px;

    border-radius: 12px;

    line-height: 1.5;
}


/* =========================
   SCANNER CARD
========================= */

.scanner-card {
    width: 100%;

    max-width: 700px;

    margin: 0 auto 25px;

    background: white;

    border-radius: 20px;

    padding: 25px;

    box-shadow:
        0 5px 25px rgba(
            15,
            23,
            42,
            0.08
        );
}


.scanner-title {
    text-align: center;

    margin-bottom: 20px;
}


.scanner-title h2 {
    margin-bottom: 8px;
}


.scanner-title p {
    color: #64748b;

    font-size: 14px;

    line-height: 1.5;
}


/* =========================
   QR READER
========================= */

#reader {
    width: 100%;

    max-width: 520px;

    margin: auto;

    overflow: hidden;

    border-radius: 14px;
}


/* html5-qrcode elements */

#reader button {
    border-radius: 8px;

    border: none;

    padding: 10px 14px;

    cursor: pointer;

    font-weight: 600;
}


#reader select {
    max-width: 100%;

    padding: 9px;

    border-radius: 8px;

    border: 1px solid #cbd5e1;
}


/* =========================
   STATUS
========================= */

.scan-status {
    text-align: center;

    margin-top: 18px;

    color: #64748b;

    font-size: 14px;

    line-height: 1.5;

    min-height: 22px;
}


/* =========================
   SCANNER CONTROLS
========================= */

.scanner-controls {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 10px;

    margin-top: 18px;
}


.scanner-control {
    border: none;

    min-height: 46px;

    padding: 12px 10px;

    border-radius: 10px;

    font-size: 13px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.2s;
}


.start-camera {
    background: #2563eb;

    color: white;
}


.start-camera:hover {
    background: #1d4ed8;
}


.switch-camera {
    background: #0f172a;

    color: white;
}


.switch-camera:hover {
    background: #1e293b;
}


.gallery-button {
    background: #e2e8f0;

    color: #0f172a;
}


.gallery-button:hover {
    background: #cbd5e1;
}


.scanner-control:disabled {
    opacity: 0.5;

    cursor: not-allowed;
}


/* Hidden gallery input */

#qr-file-input {
    display: none;
}


/* =========================
   CAMERA SELECT
========================= */

.camera-select-wrapper {
    margin-top: 15px;
}


.camera-select-wrapper label {
    display: block;

    font-size: 13px;

    font-weight: 600;

    margin-bottom: 7px;

    color: #475569;
}


#camera-select {
    width: 100%;

    padding: 11px 12px;

    border: 1px solid #cbd5e1;

    border-radius: 9px;

    background: white;

    color: #0f172a;

    font-size: 14px;

    outline: none;
}


#camera-select:focus {
    border-color: #2563eb;

    box-shadow:
        0 0 0 3px rgba(
            37,
            99,
            235,
            0.10
        );
}


/* =========================
   CAMERA HELP
========================= */

.camera-help {
    margin-top: 15px;

    padding: 13px;

    background: #f1f5f9;

    border-radius: 10px;

    color: #475569;

    font-size: 12px;

    line-height: 1.6;

    text-align: center;
}


/* =========================
   BOOK CARD
========================= */

.book-card {
    max-width: 700px;

    margin: 0 auto;

    background: white;

    border-radius: 20px;

    padding: 28px;

    box-shadow:
        0 5px 25px rgba(
            15,
            23,
            42,
            0.08
        );
}


.book-card h2 {
    font-size: 25px;

    margin-bottom: 8px;
}


.book-code {
    color: #2563eb;

    font-weight: bold;

    margin-bottom: 25px;
}


/* =========================
   BOOK DETAILS
========================= */

.book-details {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 15px;
}


.detail {
    background: #f8fafc;

    border-radius: 10px;

    padding: 14px;
}


.detail-label {
    color: #64748b;

    font-size: 12px;

    margin-bottom: 5px;
}


.detail-value {
    font-weight: 600;

    word-break: break-word;
}


.available {
    color: #15803d;
}


.unavailable {
    color: #dc2626;
}


/* =========================
   ACTION BUTTONS
========================= */

.action-buttons {
    display: flex;

    gap: 12px;

    margin-top: 25px;
}


.action-button {
    flex: 1;

    min-height: 46px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-align: center;

    padding: 13px 15px;

    border-radius: 9px;

    text-decoration: none;

    font-weight: bold;

    font-size: 14px;
}


.issue-button {
    background: #2563eb;

    color: white;
}


.issue-button:hover {
    background: #1d4ed8;
}


.issue-button.disabled {
    background: #94a3b8;

    cursor: not-allowed;

    pointer-events: none;
}


.scan-button {
    background: #0f172a;

    color: white;
}


.scan-button:hover {
    background: #1e293b;
}


/* =========================
   RESPONSIVE
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

        padding: 16px;
    }


    .header h1 {
        font-size: 25px;
    }


    .scanner-card {
        padding: 18px;

        border-radius: 16px;
    }


    .scanner-controls {
        grid-template-columns: 1fr;
    }


    .book-details {
        grid-template-columns: 1fr;
    }


    .action-buttons {
        flex-direction: column;
    }

}


@media (max-width: 500px) {

    .nav {
        grid-template-columns: 1fr;
    }


    .main {
        padding: 12px;
    }


    .scanner-title h2 {
        font-size: 20px;
    }


    .scanner-title p {
        font-size: 13px;
    }


    .book-card {
        padding: 20px;
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


        <a href="issued_books.php">
            Issue / Return
        </a>


        <a href="qr_books.php">
            QR Books
        </a>


        <a
            href="qr_scanner.php"
            class="active"
        >
            QR Scanner
        </a>


        <a href="../auth/logout.php">
            Logout
        </a>

    </nav>

</aside>



<!-- =========================
     MAIN
========================= -->

<main class="main">


    <div class="header">

        <h1>
            QR Code Scanner
        </h1>

        <p>
            Scan a book QR code using your camera
            or upload a QR image from your gallery.
        </p>

    </div>



    <!-- =========================
         ERROR
    ========================= -->

    <?php if (!empty($error)): ?>

        <div class="error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>



    <?php if ($book === null): ?>


        <!-- =========================
             SCANNER
        ========================= -->

        <div class="scanner-card">


            <div class="scanner-title">

                <h2>
                    📷 Scan Book QR Code
                </h2>

                <p>
                    Use your camera or choose a QR
                    code image from your phone gallery.
                </p>

            </div>



            <!-- QR READER -->

            <div id="reader"></div>



            <!-- STATUS -->

            <div
                class="scan-status"
                id="scan-status"
            >
                Tap "Start Camera" to begin scanning.
            </div>



            <!-- CONTROLS -->

            <div class="scanner-controls">


                <button
                    type="button"
                    class="scanner-control start-camera"
                    id="start-camera-btn"
                    onclick="startCamera()"
                >
                    📷 Start Camera
                </button>


                <button
                    type="button"
                    class="scanner-control switch-camera"
                    id="switch-camera-btn"
                    onclick="switchCamera()"
                    disabled
                >
                    🔄 Switch Camera
                </button>


                <button
                    type="button"
                    class="scanner-control gallery-button"
                    onclick="
                        document
                        .getElementById('qr-file-input')
                        .click()
                    "
                >
                    🖼️ Gallery
                </button>

            </div>



            <!-- CAMERA SELECT -->

            <div
                class="camera-select-wrapper"
                id="camera-select-wrapper"
                style="display:none;"
            >

                <label for="camera-select">
                    Select Camera
                </label>


                <select
                    id="camera-select"
                    onchange="changeCamera(this.value)"
                >
                </select>

            </div>



            <!-- HIDDEN GALLERY INPUT -->

            <input
                type="file"
                id="qr-file-input"
                accept="image/*"
                onchange="scanGalleryImage(this)"
            >



            <!-- HELP -->

            <div class="camera-help">

                <strong>Phone tip:</strong>

                Allow camera permission when your
                browser asks.

                If you do not want to use the camera,
                tap <strong>Gallery</strong> and select
                a saved QR image.

            </div>


        </div>



        <script>

        /* =====================================================
           GLOBAL VARIABLES
        ===================================================== */

        let html5QrCode = null;

        let cameras = [];

        let currentCameraIndex = 0;

        let currentCameraId = null;

        let cameraRunning = false;

        let scannerStarted = false;


        /* =====================================================
           ELEMENTS
        ===================================================== */

        const statusElement =
            document.getElementById("scan-status");

        const startButton =
            document.getElementById("start-camera-btn");

        const switchButton =
            document.getElementById("switch-camera-btn");

        const cameraSelect =
            document.getElementById("camera-select");

        const cameraSelectWrapper =
            document.getElementById(
                "camera-select-wrapper"
            );


        /* =====================================================
           STATUS
        ===================================================== */

        function setStatus(message) {

            statusElement.innerText = message;

        }


        /* =====================================================
           CREATE SCANNER
        ===================================================== */

        function createScanner() {

            if (!html5QrCode) {

                html5QrCode =
                    new Html5Qrcode("reader");

            }

        }


        /* =====================================================
           QR SUCCESS
        ===================================================== */

        function onScanSuccess(decodedText) {

            if (scannerStarted) {
                return;
            }

            scannerStarted = true;

            setStatus(
                "✅ QR code detected. Finding book..."
            );


            /*
             * Stop the camera first.
             * Then open PHP book lookup.
             */

            stopCamera().finally(function() {

                window.location.href =
                    "qr_scanner.php?book_code=" +
                    encodeURIComponent(
                        decodedText.trim()
                    );

            });

        }


        /* =====================================================
           QR FAILURE
        ===================================================== */

        function onScanFailure(error) {

            /*
             * html5-qrcode calls this continuously
             * while it is searching.
             *
             * Do not display every failure.
             */

        }


        /* =====================================================
           GET CAMERA LIST
        ===================================================== */

        async function getCameraList() {

            try {

                cameras =
                    await Html5Qrcode.getCameras();


                if (
                    !cameras ||
                    cameras.length === 0
                ) {

                    setStatus(
                        "❌ No camera was found on this device."
                    );

                    switchButton.disabled = true;

                    return false;

                }


                /*
                 * Clear old options.
                 */

                cameraSelect.innerHTML = "";


                /*
                 * Add every available camera.
                 */

                cameras.forEach(
                    function(camera, index) {

                        const option =
                            document.createElement(
                                "option"
                            );

                        option.value =
                            camera.id;

                        option.textContent =
                            camera.label ||
                            "Camera " +
                            (index + 1);

                        cameraSelect.appendChild(
                            option
                        );

                    }
                );


                /*
                 * Try to find rear camera.
                 */

                let rearCameraIndex = 0;


                cameras.forEach(
                    function(camera, index) {

                        const label =
                            (
                                camera.label ||
                                ""
                            ).toLowerCase();


                        if (
                            label.includes("back") ||
                            label.includes("rear") ||
                            label.includes("environment")
                        ) {

                            rearCameraIndex =
                                index;

                        }

                    }
                );


                currentCameraIndex =
                    rearCameraIndex;


                currentCameraId =
                    cameras[
                        currentCameraIndex
                    ].id;


                cameraSelect.value =
                    currentCameraId;


                /*
                 * Enable switch button only
                 * if multiple cameras exist.
                 */

                if (cameras.length > 1) {

                    cameraSelectWrapper
                        .style
                        .display = "block";

                    switchButton.disabled = false;

                } else {

                    cameraSelectWrapper
                        .style
                        .display = "none";

                    switchButton.disabled = true;

                }


                return true;

            }
            catch (error) {

                console.error(
                    "Camera list error:",
                    error
                );


                setStatus(
                    "❌ Unable to access cameras. " +
                    "Please allow camera permission."
                );


                return false;

            }

        }


        /* =====================================================
           START CAMERA
        ===================================================== */

        async function startCamera(
            cameraId = null
        ) {

            try {

                scannerStarted = false;

                createScanner();


                /*
                 * Get cameras if no camera was
                 * selected yet.
                 */

                if (!cameraId) {

                    const found =
                        await getCameraList();


                    if (!found) {
                        return;
                    }


                    cameraId =
                        currentCameraId;

                }


                /*
                 * If another camera is running,
                 * stop it first.
                 */

                if (cameraRunning) {

                    await stopCamera();

                }


                setStatus(
                    "Starting camera..."
                );


                /*
                 * Responsive QR scanning box.
                 */

                const config = {

                    fps: 10,

                    qrbox:
                        function(
                            viewfinderWidth,
                            viewfinderHeight
                        ) {

                            const minEdge =
                                Math.min(
                                    viewfinderWidth,
                                    viewfinderHeight
                                );


                            let boxSize =
                                Math.floor(
                                    minEdge * 0.70
                                );


                            boxSize =
                                Math.min(
                                    boxSize,
                                    300
                                );


                            return {
                                width: boxSize,
                                height: boxSize
                            };

                        }

                };


                /*
                 * Start camera.
                 */

                await html5QrCode.start(

                    cameraId,

                    config,

                    onScanSuccess,

                    onScanFailure

                );


                cameraRunning = true;

                currentCameraId =
                    cameraId;


                cameraSelect.value =
                    cameraId;


                setStatus(
                    "✅ Camera ready. " +
                    "Point it at the QR code."
                );


                /*
                 * Change button into Stop Camera.
                 */

                startButton.innerText =
                    "🛑 Stop Camera";

                startButton.onclick =
                    stopCamera;


                if (cameras.length > 1) {

                    switchButton.disabled =
                        false;

                }

            }
            catch (error) {

                console.error(
                    "Camera start error:",
                    error
                );


                cameraRunning = false;


                setStatus(
                    "❌ Unable to start camera. " +
                    "Please allow camera permission " +
                    "and try again."
                );


                startButton.innerText =
                    "📷 Start Camera";

                startButton.onclick =
                    startCamera;

            }

        }


        /* =====================================================
           STOP CAMERA
        ===================================================== */

        async function stopCamera() {

            try {

                if (
                    html5QrCode &&
                    cameraRunning
                ) {

                    await html5QrCode.stop();

                    cameraRunning = false;

                }

            }
            catch (error) {

                console.error(
                    "Camera stop error:",
                    error
                );

                cameraRunning = false;

            }


            startButton.innerText =
                "📷 Start Camera";

            startButton.onclick =
                startCamera;


            /*
             * Do not overwrite the success
             * message while redirecting.
             */

            if (!scannerStarted) {

                setStatus(
                    "Camera stopped. " +
                    "Tap Start Camera to scan."
                );

            }

        }


        /* =====================================================
           SWITCH CAMERA
        ===================================================== */

        async function switchCamera() {

            if (
                !cameras ||
                cameras.length < 2
            ) {

                setStatus(
                    "Only one camera is available."
                );

                return;

            }


            /*
             * Move to next camera.
             */

            currentCameraIndex =
                (
                    currentCameraIndex + 1
                ) % cameras.length;


            const nextCamera =
                cameras[
                    currentCameraIndex
                ];


            currentCameraId =
                nextCamera.id;


            cameraSelect.value =
                currentCameraId;


            await startCamera(
                currentCameraId
            );


            setStatus(
                "🔄 Camera switched."
            );

        }


        /* =====================================================
           CHANGE CAMERA FROM DROPDOWN
        ===================================================== */

        async function changeCamera(
            cameraId
        ) {

            if (!cameraId) {
                return;
            }


            const index =
                cameras.findIndex(
                    function(camera) {

                        return (
                            camera.id ===
                            cameraId
                        );

                    }
                );


            if (index >= 0) {

                currentCameraIndex =
                    index;

            }


            currentCameraId =
                cameraId;


            await startCamera(
                cameraId
            );

        }


        /* =====================================================
           SCAN QR FROM GALLERY
        ===================================================== */

        async function scanGalleryImage(
            input
        ) {

            const file =
                input.files &&
                input.files[0];


            if (!file) {
                return;
            }


            try {

                scannerStarted = false;


                /*
                 * Stop camera if running.
                 */

                if (cameraRunning) {

                    await stopCamera();

                }


                createScanner();


                setStatus(
                    "🖼️ Reading QR code from image..."
                );


                /*
                 * scanFile reads QR code
                 * directly from an image.
                 */

                const decodedText =
                    await html5QrCode.scanFile(
                        file,
                        true
                    );


                if (!decodedText) {

                    throw new Error(
                        "No QR code found."
                    );

                }


                scannerStarted = true;


                setStatus(
                    "✅ QR code found. " +
                    "Finding book..."
                );


                /*
                 * Send QR code to PHP.
                 */

                window.location.href =
                    "qr_scanner.php?book_code=" +
                    encodeURIComponent(
                        decodedText.trim()
                    );

            }
            catch (error) {

                console.error(
                    "Gallery scan error:",
                    error
                );


                scannerStarted = false;


                setStatus(
                    "❌ No QR code was found " +
                    "in this image. Please select " +
                    "a clear QR image."
                );

            }


            /*
             * Reset file input so the
             * same image can be selected again.
             */

            input.value = "";

        }


        /* =====================================================
           INITIALIZE
        ===================================================== */

        document.addEventListener(
            "DOMContentLoaded",
            function() {

                createScanner();


                /*
                 * Camera does NOT start automatically.
                 *
                 * This is intentional because mobile
                 * browsers handle camera permissions more
                 * reliably after a user taps a button.
                 */

                setStatus(
                    "Tap Start Camera to begin, " +
                    "or use Gallery to upload a QR image."
                );

            }
        );

        </script>


    <?php else: ?>


        <!-- =========================
             BOOK RESULT
        ========================= -->

        <div class="book-card">


            <h2>
                <?= htmlspecialchars(
                    $book["title"]
                ) ?>
            </h2>


            <div class="book-code">

                Book Code:

                <?= htmlspecialchars(
                    $book["book_code"]
                ) ?>

            </div>



            <!-- BOOK DETAILS -->

            <div class="book-details">


                <!-- AUTHOR -->

                <div class="detail">

                    <div class="detail-label">
                        Author
                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars(
                            $book["author_name"]
                            ?: "N/A"
                        ) ?>

                    </div>

                </div>



                <!-- CATEGORY -->

                <div class="detail">

                    <div class="detail-label">
                        Category
                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars(
                            $book["category_name"]
                            ?: "N/A"
                        ) ?>

                    </div>

                </div>



                <!-- ISBN -->

                <div class="detail">

                    <div class="detail-label">
                        ISBN
                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars(
                            $book["isbn"]
                            ?: "N/A"
                        ) ?>

                    </div>

                </div>



                <!-- PUBLISHER -->

                <div class="detail">

                    <div class="detail-label">
                        Publisher
                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars(
                            $book["publisher"]
                            ?: "N/A"
                        ) ?>

                    </div>

                </div>



                <!-- PUBLICATION YEAR -->

                <div class="detail">

                    <div class="detail-label">
                        Publication Year
                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars(
                            $book["publication_year"]
                            ?: "N/A"
                        ) ?>

                    </div>

                </div>



                <!-- SHELF -->

                <div class="detail">

                    <div class="detail-label">
                        Shelf
                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars(
                            $book["shelf_number"]
                            ?: "N/A"
                        ) ?>

                    </div>

                </div>



                <!-- TOTAL COPIES -->

                <div class="detail">

                    <div class="detail-label">
                        Total Copies
                    </div>

                    <div class="detail-value">

                        <?= (int)$book["quantity"] ?>

                    </div>

                </div>



                <!-- AVAILABLE COPIES -->

                <div class="detail">

                    <div class="detail-label">
                        Available Copies
                    </div>

                    <div
                        class="detail-value
                        <?= (
                            (int)$book["available_quantity"] > 0
                        )
                            ? "available"
                            : "unavailable"
                        ?>"
                    >

                        <?= (int)$book[
                            "available_quantity"
                        ] ?>

                    </div>

                </div>


            </div>



            <!-- =========================
                 ACTION BUTTONS
            ========================= -->

            <div class="action-buttons">


                <?php if (
                    (int)$book["available_quantity"] > 0
                ): ?>

                    <a
                        href="issued_books.php?book_id=<?= (int)$book["id"] ?>"
                        class="action-button issue-button"
                    >
                        📖 Issue This Book
                    </a>

                <?php else: ?>

                    <span
                        class="
                            action-button
                            issue-button
                            disabled
                        "
                    >
                        ❌ Currently Unavailable
                    </span>

                <?php endif; ?>


                <a
                    href="qr_scanner.php"
                    class="action-button scan-button"
                >
                    📷 Scan Another
                </a>


            </div>


        </div>


    <?php endif; ?>


</main>


</body>

</html>