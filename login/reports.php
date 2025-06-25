<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
include 'db_connect.php';
include 'auth_check.php'; // เรียกใช้งานการตรวจสอบการเข้าสู่ระบบและสถานะผู้ใช้

// ฟังก์ชันส่งข้อความไปยัง Telegram
function sendTelegramMessage($chatId, $message, $botToken) {
    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    
    // สร้างข้อมูลที่ต้องการส่งไปยัง Telegram
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
    ];

    // ใช้ cURL เพื่อส่งคำขอ
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // ปิดการตรวจสอบใบรับรอง SSL
    $result = curl_exec($ch);
    curl_close($ch);

    // ตรวจสอบผลลัพธ์
    if ($result === false) {
        error_log("Error sending message to Telegram: " . curl_error($ch));
    } else {
        error_log("Message sent successfully to Telegram: $result");
    }
    return $result !== false;
}

// BOT Token ของคุณ
$telegramBotToken = "7668345720:AAGIKyTGFQGUGiMOjbax5Mv9Y30Chydnqc4";

// ดึงข้อมูล personnel_id จาก session
$personnel_id = $_SESSION['personnel_id'];  // ตรวจสอบว่าผู้ใช้ล็อกอินอยู่

// ดึง Role_ID ของผู้ใช้จากฐานข้อมูล
$sql = "SELECT Role_ID FROM personnel WHERE Personnel_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $personnel_id);
$stmt->execute();
$stmt->bind_result($user_role);
$stmt->fetch();
$stmt->close();

// ฟังก์ชันดึง Telegram_ID จาก Personnel_ID
function getTelegramIdByPersonnelId($personnel_id, $conn) {
    $sql = "SELECT Telegram_ID FROM personnel WHERE Personnel_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $personnel_id);
    $stmt->execute();
    $stmt->bind_result($telegram_id);
    $stmt->fetch();
    $stmt->close();
    return $telegram_id;
}

// ฟังก์ชันส่งข้อความไปยังผู้ใช้ตาม Role ที่ระบุ
function sendToRoles($roles, $message, $conn, $telegramBotToken) {
    // สร้าง SQL query เพื่อดึง Telegram_ID ของผู้ใช้ที่มี Role_ID ในรายการ $roles
    $role_placeholders = implode(',', array_fill(0, count($roles), '?'));
    $sql = "SELECT Telegram_ID FROM personnel WHERE Role_ID IN ($role_placeholders) AND Telegram_ID IS NOT NULL";
    $stmt = $conn->prepare($sql);
    
    // สร้าง dynamic types string สำหรับ bind_param (ในที่นี้เป็น integer ทั้งหมด)
    $types = str_repeat("i", count($roles));
    $stmt->bind_param($types, ...$roles);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $telegram_id = $row['Telegram_ID'];
        sendTelegramMessage($telegram_id, $message, $telegramBotToken);
    }
    $stmt->close();
}

// ฟังก์ชันการอนุมัติ
function approveBooking($booking_id, $approver_id, $conn, $telegramBotToken, $approval_stage) {
    // กำหนดสถานะตามระยะการอนุมัติ
    if ($approval_stage == 1) {
        $status_id = 2; // อนุมัติระยะแรก (รอดำเนินการ)
    } elseif ($approval_stage == 2) {
        $status_id = 4; // อนุมัติสุดท้าย
    } else {
        return; // ถ้าไม่มีระยะการอนุมัติที่ระบุ, ไม่ทำการอัปเดต
    }

    // อัปเดตสถานะการจองตามระยะการอนุมัติ
    $sql = "UPDATE booking SET Status_ID = ?, Approver_ID = ?, Approval_Stage = ? WHERE Booking_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $status_id, $approver_id, $approval_stage, $booking_id);
    $stmt->execute();
    $stmt->close();

    // ดึงข้อมูลรายละเอียดของการจอง (เช่น หัวข้อ, ห้องประชุม และ Personnel_ID ของผู้จอง)
    $sql = "SELECT h.Hall_Name, b.Topic_Name, b.Personnel_ID FROM booking b
            LEFT JOIN hall h ON b.Hall_ID = h.Hall_ID
            WHERE b.Booking_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($hall_name, $topic_name, $booker_personnel_id);
    $stmt->fetch();
    $stmt->close();

    // กำหนดข้อความแจ้งเตือนตามระยะการอนุมัติ
    if ($approval_stage == 1) {
        $message = "การจองห้องประชุมหมายเลข $booking_id ได้รับการอนุมัติจากรองผู้อำนวยการแล้ว\nหัวข้อ: $topic_name\nห้องประชุม: $hall_name";
        // ส่งข้อความแจ้งเตือนไปยังผู้ใช้ที่มี Role_ID 2 และ 3 (ผู้ดูแลและผู้อนุมัติในรอบแรก)
        sendToRoles([2, 3], $message, $conn, $telegramBotToken);
    } elseif ($approval_stage == 2) {
        $message = "การจองห้องประชุมหมายเลข $booking_id ได้รับการอนุมัติจากผู้อำนวยการแล้ว\nหัวข้อ: $topic_name\nห้องประชุม: $hall_name";
        // ส่งข้อความแจ้งเตือนไปยังผู้ใช้ที่มี Role_ID 2 และ 4 (ผู้ดูแลและผู้อนุมัติรอบสุดท้าย)
        sendToRoles([2, 4], $message, $conn, $telegramBotToken);
        // หลังจากอนุมัติสุดท้ายแล้ว ให้ส่งแจ้งเตือนไปยังผู้จองด้วย
        $telegram_id = getTelegramIdByPersonnelId($booker_personnel_id, $conn);
        if (!empty($telegram_id)) {
            sendTelegramMessage($telegram_id, $message, $telegramBotToken);
        } else {
            error_log("ไม่มี Telegram_ID สำหรับผู้จอง (Personnel_ID: $booker_personnel_id)");
        }
    }
}

// ฟังก์ชันการไม่อนุมัติ
function rejectBooking($booking_id, $approver_id, $conn, $telegramBotToken) {
    // อัปเดตสถานะการจองเป็น "ไม่อนุมัติ"
    $sql = "UPDATE booking SET Status_ID = 3, Approver_ID = ? WHERE Booking_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $approver_id, $booking_id);
    $stmt->execute();
    $stmt->close();

    // ดึงข้อมูลรายละเอียดของการจอง
    $sql = "SELECT h.Hall_Name, b.Topic_Name, b.Personnel_ID FROM booking b
            LEFT JOIN hall h ON b.Hall_ID = h.Hall_ID
            WHERE b.Booking_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($hall_name, $topic_name, $booker_personnel_id);
    $stmt->fetch();
    $stmt->close();

    // สร้างข้อความแจ้งเตือนการไม่อนุมัติ
    $message = "การจองห้องประชุมหมายเลข $booking_id ไม่ได้รับการอนุมัติ\nหัวข้อ: $topic_name\nห้องประชุม: $hall_name";
    // ส่งข้อความแจ้งเตือนไปยังผู้จอง
    $telegram_id = getTelegramIdByPersonnelId($booker_personnel_id, $conn);
    if (!empty($telegram_id)) {
        sendTelegramMessage($telegram_id, $message, $telegramBotToken);
    } else {
        error_log("ไม่มี Telegram_ID สำหรับผู้จอง (Personnel_ID: $booker_personnel_id)");
    }
}

// ตรวจสอบการเข้าสู่ระบบและ Role_ID ของผู้ใช้
$user_role = $_SESSION['role_id'];

// กำหนด SQL query ตาม Role_ID
if ($user_role == 2) { // Admin
    $sql = "SELECT 
                b.Booking_ID,
                b.Topic_Name,
                h.Hall_Name,
                CONCAT(p.First_Name, ' ', p.Last_Name) AS Booker_Name,
                b.Date_Start,
                b.Time_Start,
                b.Time_End,
                b.Attendee_Count,
                s.Status_ID,
                CONCAT(a.First_Name, ' ', a.Last_Name) AS Approver_Name
            FROM 
                booking b
            LEFT JOIN personnel p ON b.Personnel_ID = p.Personnel_ID
            LEFT JOIN hall h ON b.Hall_ID = h.Hall_ID
            LEFT JOIN booking_status s ON b.Status_ID = s.Status_ID
            LEFT JOIN personnel a ON b.Approver_ID = a.Personnel_ID
            ORDER BY b.Booking_ID DESC";
} elseif ($user_role == 3) { // ผู้อนุมัติ (ดูเฉพาะอนุมัติระยะแรก)
    $sql = "SELECT 
                b.Booking_ID,
                b.Topic_Name,
                h.Hall_Name,
                CONCAT(p.First_Name, ' ', p.Last_Name) AS Booker_Name,
                b.Date_Start,
                b.Time_Start,
                b.Time_End,
                b.Attendee_Count,
                s.Status_ID,
                CONCAT(a.First_Name, ' ', a.Last_Name) AS Approver_Name
            FROM 
                booking b
            LEFT JOIN personnel p ON b.Personnel_ID = p.Personnel_ID
            LEFT JOIN hall h ON b.Hall_ID = h.Hall_ID
            LEFT JOIN booking_status s ON b.Status_ID = s.Status_ID
            LEFT JOIN personnel a ON b.Approver_ID = a.Personnel_ID
            WHERE s.Status_ID = 2  -- เฉพาะอนุมัติระยะแรก
            ORDER BY b.Booking_ID DESC";
} elseif ($user_role == 4) { // รอง (ดูเฉพาะรอตรวจสอบ)
    $sql = "SELECT 
                b.Booking_ID,
                b.Topic_Name,
                h.Hall_Name,
                CONCAT(p.First_Name, ' ', p.Last_Name) AS Booker_Name,
                b.Date_Start,
                b.Time_Start,
                b.Time_End,
                b.Attendee_Count,
                s.Status_ID,
                CONCAT(a.First_Name, ' ', a.Last_Name) AS Approver_Name
            FROM 
                booking b
            LEFT JOIN personnel p ON b.Personnel_ID = p.Personnel_ID
            LEFT JOIN hall h ON b.Hall_ID = h.Hall_ID
            LEFT JOIN booking_status s ON b.Status_ID = s.Status_ID
            LEFT JOIN personnel a ON b.Approver_ID = a.Personnel_ID
            WHERE s.Status_ID = 1  -- เฉพาะรอตรวจสอบ (อนุมัติระยะแรก)
            ORDER BY b.Booking_ID DESC";
} else { // ผู้ใช้ทั่วไป (Role_ID = 1) หรือกรณีอื่น ๆ
    $sql = "SELECT 
                b.Booking_ID,
                b.Topic_Name,
                h.Hall_Name,
                CONCAT(p.First_Name, ' ', p.Last_Name) AS Booker_Name,
                b.Date_Start,
                b.Time_Start,
                b.Time_End,
                b.Attendee_Count,
                s.Status_ID,
                CONCAT(a.First_Name, ' ', a.Last_Name) AS Approver_Name
            FROM 
                booking b
            LEFT JOIN personnel p ON b.Personnel_ID = p.Personnel_ID
            LEFT JOIN hall h ON b.Hall_ID = h.Hall_ID
            LEFT JOIN booking_status s ON b.Status_ID = s.Status_ID
            LEFT JOIN personnel a ON b.Approver_ID = a.Personnel_ID
            WHERE s.Status_ID = 1  -- เฉพาะรอตรวจสอบ (อนุมัติระยะแรก)
            ORDER BY b.Booking_ID DESC";
}

// ดึงข้อมูลการจอง
$result = $conn->query($sql);
if (!$result) {
    die("Error retrieving data: " . $conn->error);
}

// เมื่อผู้ใช้คลิกอนุมัติหรือไม่อนุมัติ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $booking_id = $_POST['booking_id'];
        $approval_stage = $_POST['approval_stage'];  // รับค่าระยะการอนุมัติ
        approveBooking($booking_id, $_SESSION['personnel_id'], $conn, $telegramBotToken, $approval_stage);
    }
    if (isset($_POST['reject'])) {
        $booking_id = $_POST['booking_id'];
        rejectBooking($booking_id, $_SESSION['personnel_id'], $conn, $telegramBotToken);
    }
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.bootstrap5.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
    @media (max-width: 991px) {
        #navbarNav {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

    }

    html,
    body {
        margin: 0;
        padding: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    body {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* จัดข้อความให้อยู่กึ่งกลางทั้งแนวตั้งและแนวนอน */
    .table td,
    .table th {
        padding-top: 15px;
        /* Padding ด้านบน */
        padding-bottom: 15px;
        /* Padding ด้านล่าง */
        text-align: center;
        /* จัดกึ่งกลางแนวนอน */
        vertical-align: middle;
        /* จัดกึ่งกลางแนวตั้ง */
    }

    main {
        flex-grow: 1;
        /* ให้ส่วนเนื้อหาขยายเต็มพื้นที่ที่เหลือ */
        overflow: auto;
        padding-bottom: 20px;
    }

    .container-custom {
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        max-width: 1200px;
        width: 100%;
        margin-bottom: 20px;
    }

    .full-height {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        height: calc(100vh - 56px);
        position: relative;
        padding-top: 60px;
        padding-bottom: 20px;
        flex-grow: 1;
        overflow: auto;
        padding-bottom: 20px;
    }

    .text-center {
        color: white;
        padding: 20px;
        border-radius: 5px 5px 0 0;
        height: 70px;
        display: flex;
        align-items: center;
        border: 1px solid #e0e0e0;
        max-width: 1200px;
        width: 100%;

    }

    .text-warning {
        color: #ffc107;
        /* สีเหลือง */

    }

    .text-success {
        color: #28a745;
        /* สีเขียว */

    }

    .text-danger {
        color: #dc3545;
        /* สีแดง */

    }

    .footer {
        width: 100%;
        background-color: #f8f9fa;
        padding: 20px;
        font-size: 16px;
        color: #6c757d;
        margin-top: auto;
        position: relative;
    }

    table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        overflow: hidden;
    }

    th,
    td {
        border: 1px solid #e0e0e0;
        padding: 20px;
    }

    th {
        background-color: #f8f9fa;
    }

    td {
        background-color: #ffffff;
    }

    td img {
        border-radius: 5px;
        /* กำหนดความโค้งให้กับรูปภาพ */
    }

    /* Navbar brand logo */
    .navbar-brand .responsive-img {
        max-width: 100%;
        height: auto;
    }

    /* Adjusting padding for Navbar */
    .navbar {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    /* Adjust padding for nav-link */
    .nav-link {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    /* Dropdown menu styling */
    .nav-item .dropdown-menu {
        background-color: rgb(1, 20, 69);
        color: #ffffff;
    }

    .dropdown-menu .dropdown-item {
        color: #ffffff;
    }

    .dropdown-menu .dropdown-item:hover {
        background-color: #e0a444;
        color: #ffffff;
    }

    .btn-outline-custom {
        color: #010f33; /* สีข้อความ */
        border-color: #010f33; /* สีขอบ */
    }

    .btn-outline-custom:hover {
        background-color: #010f33; /* สีพื้นหลังเมื่อ hover */
        color: #fff; /* สีข้อความเมื่อ hover */
    }

    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark p-3" style="background-color: #010f33;">
        <div class="container-fluid">
            <a href="main.php" class="navbar-brand d-flex align-items-center">
                <img class="responsive-img" src="LOGO.png" alt="system booking" width="45" height="45">
                <span class="ms-3">ระบบจองห้องประชุม</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a href="main.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'main.php') ? 'active' : ''; ?>">หน้าหลัก</a>
                    </li>

                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>

                    <!-- Dropdown เมนูสำหรับ "รายการจองของฉัน" -->
                    <li class="nav-item dropdown">
                        <!-- เช็คไฟล์ PHP สำหรับ active -->
                        <a class="nav-link dropdown-toggle <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['upcoming_bookings.php', 'disactive_bookings.php', 'active_bookings.php']) ? 'active' : ''); ?>"
                            href="#" id="myBookingsDropdown" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            รายการจองของฉัน
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="myBookingsDropdown">
                            <li><a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'upcoming_bookings.php') ? 'active' : ''; ?>"
                                    href="upcoming_bookings.php">รอตรวจสอบ</a></li>
                            <li><a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'active_bookings.php') ? 'active' : ''; ?>"
                                    href="active_bookings.php">อนุมัติ</a></li>
                            <li><a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'disactive_bookings.php') ? 'active' : ''; ?>"
                                    href="disactive_bookings.php">ไม่อนุมัติ</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="booking.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'booking.php') ? 'active' : ''; ?>">จองห้อง</a>
                    </li>

                    <?php if ($_SESSION['role_id'] == 2): ?>
                    <li class="nav-item">
                        <a href="members.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'members.php') ? 'active' : ''; ?>">สมาชิก</a>
                    </li>
                    <li class="nav-item">
                        <a href="reports.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">รายงาน</a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">สถิติ</a>
                    </li>
                    <?php elseif ($_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 4): ?>
                    <li class="nav-item">
                        <a href="reports.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">รายงาน</a>
                    </li>
                    <?php endif; ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            สวัสดี, <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="edit_profile.php">แก้ไขข้อมูล</a></li>
                            <li><a class="dropdown-item" href="logout.php">ออกจากระบบ</a></li>
                        </ul>
                    </li>

                    <?php else: ?>
                    <li class="nav-item">
                        <a href="booking.php" class="nav-link active">จองห้อง</a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">เข้าสู่ระบบ</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="full-height">
        <div class="text-center" style="background-color: #010f33;">
            <div style="font-size: 20px">รายงาน</div>
        </div>
        <div class="container-custom">
            <!-- แสดงข้อความที่นี่ -->
            <?php
        if (isset($_SESSION['message'])) {
            echo $_SESSION['message'];
            unset($_SESSION['message']);
        }
        ?>
            <?php
        // ดึงข้อมูลการจอง
        $sql = "SELECT 
                    b.Booking_ID,
                    b.Topic_Name,
                    b.Booking_File_Path,  -- เพิ่มคอลัมน์นี้
                    h.Hall_Name,
                    CONCAT(p.First_Name, ' ', p.Last_Name) AS Booker_Name,
                    b.Date_Start,
                    b.Time_Start,
                    b.Time_End,
                    b.Attendee_Count,
                    s.Status_ID,
                    s.Status_Name,
                    CONCAT(a.First_Name, ' ', a.Last_Name) AS Approver_Name,
                    b.Booking_Detail,
                    b.Approval_Stage
                FROM 
                    booking b
                LEFT JOIN personnel p ON b.Personnel_ID = p.Personnel_ID
                LEFT JOIN hall h ON b.Hall_ID = h.Hall_ID
                LEFT JOIN booking_status s ON b.Status_ID = s.Status_ID
                LEFT JOIN personnel a ON b.Approver_ID = a.Personnel_ID
                ORDER BY b.Booking_ID DESC";
        $result = $conn->query($sql);

        // ถ้าไม่มีข้อมูลให้ซ่อนตารางโดยใช้ CSS
        $tableStyle = "";
        if (!($result && $result->num_rows > 0)) {
            $tableStyle = "display: none;";
        }
        ?>
            <table id="member-table" class="table table-striped" style="width:100%; <?= $tableStyle ?>">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>หัวข้อ</th>
                        <th>ชื่อห้อง</th>
                        <th>ชื่อผู้จอง</th>
                        <th>วันที่และเวลา</th>
                        <th>จำนวน</th>
                        <th>สถานะ (Status)</th>
                        <th>เหตุผล (Reason)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <?php 
                        // ถ้า role เป็น 3 หรือ 4 และสถานะของรายการเป็น 3 (ไม่อนุมัติ) หรือ 4 (อนุมัติแล้ว)
                        // ให้ข้ามรายการนั้นไปโดยไม่แสดง
                        if (($_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 4) && 
                            ($row['Status_ID'] == 3 || $row['Status_ID'] == 4)) {
                            continue;
                        }
                        ?>
                    <tr>
                        <td><?php echo $row['Booking_ID']; ?></td>
                        <td><?php echo htmlspecialchars($row['Topic_Name']); ?></td>
                        <td><?php echo htmlspecialchars($row['Hall_Name']); ?></td>
                        <td><?php echo htmlspecialchars($row['Booker_Name']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($row['Date_Start']) . ' ' . 
                                    htmlspecialchars($row['Time_Start']) . ' - ' . 
                                    htmlspecialchars($row['Time_End']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['Attendee_Count']); ?></td>
                        <td>
                            <?php if ((int)$row['Status_ID'] === 1): ?>
                            <span class="text-warning">รอตรวจสอบ</span>
                            <?php elseif ((int)$row['Status_ID'] === 2): ?>
                            <span class="text-warning">รอดำเนินการ</span>
                            <?php elseif ((int)$row['Status_ID'] === 3): ?>
                            <span class="text-danger">ไม่อนุมัติ</span>
                            <?php elseif ((int)$row['Status_ID'] === 4): ?>
                            <span class="text-success">อนุมัติแล้ว</span>
                            <?php else: ?>
                            <span class="text-muted">ไม่ทราบสถานะ</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- ปุ่มรายละเอียด (แสดงสำหรับทุกสถานะ) -->
                            <button type="button" class="btn btn-outline-dark btn-sm ms-2 detail-btn"
                                data-id="<?php echo $row['Booking_ID']; ?>">
                                <i class="fas fa-info-circle"></i>
                            </button>

                            <?php if ((int)$row['Status_ID'] === 1): ?>
                            <!-- ปุ่มอนุมัติระยะแรก -->
                            <?php if ($_SESSION['role_id'] == 2 || $_SESSION['role_id'] == 4): ?>
                            <button type="button" class="btn btn-outline-success btn-sm ms-2" data-bs-toggle="modal"
                                data-bs-target="#approveRejectModal<?php echo $row['Booking_ID']; ?>">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <?php endif; ?>
                            <!-- ปุ่มลบ (ทุกระยะให้แอดมินสามารถลบได้) -->
                            <?php if ($_SESSION['role_id'] == 2): ?>
                            <a href="delete_booking.php?id=<?php echo $row['Booking_ID']; ?>"
                                class="btn btn-outline-danger btn-sm ms-2"
                                onclick="return confirm('คุณแน่ใจว่าต้องการลบรายการจองนี้?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                            <?php elseif ((int)$row['Status_ID'] === 2): ?>
                            <!-- ปุ่มอนุมัติระยะสุดท้าย -->
                            <?php if ($_SESSION['role_id'] == 2 || $_SESSION['role_id'] == 3): ?>
                            <button type="button" class="btn btn-outline-success btn-sm ms-2" data-bs-toggle="modal"
                                data-bs-target="#approveRejectModal<?php echo $row['Booking_ID']; ?>">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <?php endif; ?>
                            <!-- ปุ่มลบ -->
                            <?php if ($_SESSION['role_id'] == 2): ?>
                            <a href="delete_booking.php?id=<?php echo $row['Booking_ID']; ?>"
                                class="btn btn-outline-danger btn-sm ms-2"
                                onclick="return confirm('คุณแน่ใจว่าต้องการลบรายการจองนี้?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                            <?php elseif ((int)$row['Status_ID'] === 3): ?>
                            <!-- ไม่อนุมัติ -->
                            <?php if ($_SESSION['role_id'] == 2 || $_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 4): ?>
                            <!-- เมื่อการจองไม่ได้รับการอนุมัติ -->
                            <?php endif; ?>
                            <!-- ปุ่มลบ -->
                            <?php if ($_SESSION['role_id'] == 2): ?>
                            <a href="delete_booking.php?id=<?php echo $row['Booking_ID']; ?>"
                                class="btn btn-outline-danger btn-sm ms-2"
                                onclick="return confirm('คุณแน่ใจว่าต้องการลบรายการจองนี้?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                            <?php elseif ((int)$row['Status_ID'] === 4): ?>
                            <!-- เมื่อการจองได้รับการอนุมัติแล้ว -->
                            <!-- ปุ่มลบ -->
                            <?php if ($_SESSION['role_id'] == 2): ?>
                            <a href="delete_booking.php?id=<?php echo $row['Booking_ID']; ?>"
                                class="btn btn-outline-danger btn-sm ms-2"
                                onclick="return confirm('คุณแน่ใจว่าต้องการลบรายการจองนี้?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                            <?php endif; ?>

                            <!-- Modal การอนุมัติ -->
                            <div class="modal fade" id="approveRejectModal<?php echo $row['Booking_ID']; ?>"
                                tabindex="-1" aria-labelledby="approveRejectModalLabel<?php echo $row['Booking_ID']; ?>"
                                aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"
                                                id="approveRejectModalLabel<?php echo $row['Booking_ID']; ?>">
                                                การจัดการการจอง</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>คุณต้องการดำเนินการอย่างไรกับการจองนี้?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <!-- ปุ่มอนุมัติระยะแรก -->
                                            <?php if ($_SESSION['role_id'] == 4 || ($_SESSION['role_id'] == 2 && $row['Status_ID'] == 1)): ?>
                                            <form method="POST" action="">
                                                <input type="hidden" name="booking_id"
                                                    value="<?php echo $row['Booking_ID']; ?>">
                                                <input type="hidden" name="approval_stage" value="1">
                                                <button type="submit" name="approve" class="btn btn-success">
                                                    <i class="fas fa-check-circle"></i> อนุมัติ
                                                </button>
                                            </form>
                                            <?php endif; ?>

                                            <!-- ปุ่มอนุมัติระยะสุดท้าย -->
                                            <?php if ($_SESSION['role_id'] == 3 || ($_SESSION['role_id'] == 2 && $row['Status_ID'] == 2)): ?>
                                            <form method="POST" action="">
                                                <input type="hidden" name="booking_id"
                                                    value="<?php echo $row['Booking_ID']; ?>">
                                                <input type="hidden" name="approval_stage" value="2">
                                                <button type="submit" name="approve" class="btn btn-success">
                                                    <i class="fas fa-check-circle"></i> อนุมัติ
                                                </button>
                                            </form>
                                            <?php endif; ?>

                                            <!-- ปุ่มไม่อนุมัติ -->
                                            <form method="POST" action="">
                                                <input type="hidden" name="booking_id"
                                                    value="<?php echo $row['Booking_ID']; ?>">
                                                <button type="submit" name="reject"
                                                    class="btn btn-danger">ไม่อนุมัติ</button>
                                            </form>

                                            <!-- ปุ่มปิด -->
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">ยกเลิก</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรายละเอียดการจอง -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="detailModalLabel">รายละเอียดการจอง</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- เนื้อหาจะแสดงที่นี่หลังโหลดจาก room_detail_upcom.php -->
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>




    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.bootstrap5.js"></script>
    <script>
    $(document).ready(function() {
        $('#member-table').dataTable();
        // (ถ้าไม่ใช้ Ajax ของ DataTables ให้ลบบรรทัดต่อไปนี้)
        // $('#member-table').DataTable().ajax.reload(null, false);

        $('.detail-btn').on('click', function() {
            var bookingId = $(this).data('id');
            $('#detailModal .modal-body').html('Loading...');
            $.ajax({
                url: 'room_detail_upcom.php',
                type: 'GET',
                data: {
                    id: bookingId
                },
                success: function(response) {
                    $('#detailModal .modal-body').html(response);
                },
                error: function() {
                    $('#detailModal .modal-body').html('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                }
            });
            var detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
            detailModal.show();
        });
    });
    </script>


</body>

</html>