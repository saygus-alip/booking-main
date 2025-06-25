<?php
session_start();
include 'auth_check.php'; // เรียกใช้งานการตรวจสอบการเข้าสู่ระบบและสถานะผู้ใช้
include 'db_connect.php';

// ดึงข้อมูลจำนวนห้องในแต่ละสถานะ
$sql_pending = "SELECT COUNT(*) AS rooms_pending FROM booking WHERE Status_ID = 1";
$sql_approved = "SELECT COUNT(*) AS rooms_approved FROM booking WHERE Status_ID = 4";
$sql_rejected = "SELECT COUNT(*) AS rooms_rejected FROM booking WHERE Status_ID = 3";
$sql_all = "SELECT COUNT(*) AS total_rooms FROM booking";

// ส่งคำสั่ง SQL ไปยังฐานข้อมูล
$result_pending = $conn->query($sql_pending);
$result_approved = $conn->query($sql_approved);
$result_rejected = $conn->query($sql_rejected);
$result_all = $conn->query($sql_all);

// ตรวจสอบผลลัพธ์
if ($result_pending->num_rows > 0) {
    $pending = $result_pending->fetch_assoc();
    $rooms_pending = $pending['rooms_pending'];
} else {
    $rooms_pending = 0;
}

if ($result_approved->num_rows > 0) {
    $approved = $result_approved->fetch_assoc();
    $rooms_approved = $approved['rooms_approved'];
} else {
    $rooms_approved = 0;
}

if ($result_rejected->num_rows > 0) {
    $rejected = $result_rejected->fetch_assoc();
    $rooms_rejected = $rejected['rooms_rejected'];
} else {
    $rooms_rejected = 0;
}

if ($result_all->num_rows > 0) {
    $all = $result_all->fetch_assoc();
    $total_rooms = $all['total_rooms'];
} else {
    $total_rooms = 0;
}
$conn->close();

$sql = "SELECT Hall_Name, Dot_Color FROM hall";
$result = $conn->query($sql);
$rooms = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
        $rooms[] = $row;
    }
}
$conn->close();
// สมมุติว่าเราเลือกเอาค่าสีของห้องแรกมาแสดงใน dashboard:
$room_dot_color = isset($rooms[0]['Dot_Color']) ? $rooms[0]['Dot_Color'] : '#ccc';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="login/LOGO.png" sizes="32x32">
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
        }

        .calendar td {
            height: 80px;
        }

        .booking-dot {
            width: 8px;
            height: 8px;
        }

        .activing {
            margin-top: 15px;
        }

        .disactiving {
            margin-top: 15px;
        }
    }

    html,
    body {
        margin: 0;
        padding: 0;
    }

    body {
        margin: 0;
        padding: 0;
    }

    main {
        margin: 0;
        padding: 0;
    }

    .responsive-img {
        max-width: 100%;
        height: auto;
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

    .full-height {
        margin-top: 50px;
    }

    .full-height {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        position: relative;
        padding-bottom: 20px;
        flex-grow: 1;
        overflow: auto;
    }

    .container-custom-1 {
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        max-width: 1200px;
        width: 100%;
        /* กำหนดระยะห่างระหว่างปฏิทินกับ footer */
    }

    .text-center {
        color: white;
        padding: 20px;
        border-radius: 5px 5px 0 0;
        height: 70px;
        display: flex;
        align-items: center;
        max-width: 1200px;
        width: 100%;

    }

    .full-height2 {
        margin-top: 50px;
    }

    .full-height2 {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        position: relative;
        padding-bottom: 20px;
        flex-grow: 1;
        overflow: auto;
        padding-bottom: 20px;
    }

    .container-custom-2 {
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        max-width: 1200px;
        width: 100%;
        margin-bottom: 100px;
        /* กำหนดระยะห่างระหว่างปฏิทินกับ footer */
        flex-wrap: wrap;
        /* ช่วยให้รายการที่เกินขนาดไปยังบรรทัดถัดไป */
    }

    .text-center2 {
        color: white;
        padding: 20px;
        border-radius: 5px 5px 0 0;
        height: 70px;
        display: flex;
        align-items: center;
        max-width: 1200px;
        width: 100%;

    }

    .container-custom-2 {
        background-color: #fff;
        padding: 30px;
        display: flex;
        border-radius: 5px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        max-width: 1200px;
        width: 100%;
        margin-bottom: 20px;
    }


    .footer {
        position: fixed;
        bottom: 0;
        width: 100%;
        background-color: #f8f9fa;
        padding: 20px;
        font-size: 16px;
        color: #6c757d;
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
    }

    #month-year {
        font-size: 1.5em;
        text-align: left;
        color: #fff;
        margin: 0;
    }

    table.calendar {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        width: 14%;
        text-align: center;
        padding: 10px;
        border: 1px solid #ddd;
    }

    td {
        cursor: pointer;
    }

    td:hover {
        background-color: #f0f0f0;
    }

    .navigation {
        display: flex;
        gap: 10px;
    }

    /* ตั้งค่าขนาดและลักษณะของเซลล์ในปฏิทิน */
    .calendar {
        border-collapse: collapse;
        width: 100%;
        table-layout: fixed;
    }

    .calendar th,
    .calendar td {
        width: 14.2%;
        height: 100px;
        text-align: center;
        vertical-align: top;
        position: relative;
        border: 1px solid #ddd;
        padding: 5px;
    }

    /* จุดสีของกิจกรรม */
    .booking-dots {
        display: flex;
        justify-content: center;
        margin-top: 5px;
        gap: 5px;
    }

    .booking-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    /* สีของจุดที่ตรงกับ Message Box */
    .dot-gray {
        background-color: #999999;
    }

    /* สีเทา */
    .dot-red {
        background-color: #d0021b;
    }

    /* สีแดง */
    .dot-yellow {
        background-color: #f5a623;
    }

    /* สีเหลือง */
    .dot-blue {
        background-color: #007bff;
    }

    /* สีน้ำเงิน */
    .dot-green {
        background-color: #4CAF50;
    }

    /* สีเขียว */

    /* ไฮไลต์วันที่ปัจจุบัน */
    .current-day {
        background-color: #010f33;
        color: white;
        font-weight: bold;
    }

    /* รูปแบบการจัดวางข้อมูลใน Message Box */
    .booking-item {
        display: flex;
        align-items: center;
        gap: 10px;
        /* ระยะห่างระหว่างจุดสีและข้อความ */
        margin-bottom: 10px;
    }

    /* จุดสีใน Modal */
    .booking-item .booking-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
        /* ป้องกันไม่ให้จุดสีเปลี่ยนขนาด */
    }

    /* ข้อความกิจกรรม */
    .booking-item p {
        margin: 0;
        font-size: 16px;
    }


    .dot-red {
        background-color: #d0021b;
    }

    .dot-blue {
        background-color: #007bff;
    }

    .dot-green {
        background-color: #4CAF50;
    }

    .dot-yellow {
        background-color: #f5a623;
    }

    .dot-orange {
        background-color: #FFA500;
    }

    .dot-purple {
        background-color: #800080;
    }

    .upcoming {
        width: 350px;
        height: 160px;
        background-color: rgb(1, 66, 136);
        border-radius: 10px;
        margin-right: 15px;
    }

    .activing {
        width: 350px;
        height: 160px;
        background-color: rgb(0, 79, 3);
        border-radius: 10px;
        margin-right: 15px;
    }

    .disactiving {
        width: 350px;
        height: 160px;
        background-color: rgb(124, 106, 0);
        border-radius: 10px;
        margin-right: 15px;
    }

    .allroom {
        width: 350px;
        height: 160px;
        margin-top: 15px;
        background-color: rgb(106, 1, 1);
        border-radius: 10px;
        margin-right: 15px;
    }

    .allbooking {
        width: 350px;
        height: 160px;
        background-color: rgb(72, 1, 81);
        margin-top: 15px;
        border-radius: 10px;
        margin-right: 15px;

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
                                    href="disactive_bookings.php">อนุมัติ</a></li>
                            <li><a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'disactive_bookings.php') ? 'active' : ''; ?>"
                                    href="active_bookings.php">ไม่อนุมัติ</a></li>
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
                        <a href="booking.php" class="nav-link">จองห้อง</a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">เข้าสู่ระบบ</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>


    <!-- Modal ต้อนรับ -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalLabel">Alert</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($_SESSION['username'])): ?>
                    Hello <?php echo $_SESSION['username']; ?>, Welcome to the meeting room booking website!
                    <?php else: ?>
                    Hello Teacher, Welcome to the meeting room booking website!
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรายละเอียดการจอง -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">รายละเอียดกิจกรรม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bookingModalBody">
                    <!-- รายละเอียดจะถูกเพิ่มที่นี่ -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>


    <div class="full-height">
        <div class="text-center" style="background-color: #010f33;">
            <div class="calendar-header">
                <h2 id="month-year"></h2>
                <div class="navigation">
                    <button id="prev-month" class="btn btn-outline-secondary">
                        < </button>
                            <button id="next-month" class="btn btn-outline-secondary">></button>
                </div>
            </div>
        </div>
        <div class="container-custom-1">
            <table class="calendar">
                <thead>
                    <tr>
                        <th>อา.</th>
                        <th>จ.</th>
                        <th>อ.</th>
                        <th>พ.</th>
                        <th>พฤ.</th>
                        <th>ศ.</th>
                        <th>ส.</th>
                    </tr>
                </thead>
                <tbody id="calendar-body"></tbody>
            </table>
        </div>
    </div>

    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
    <div class="full-height2">
        <div class="text-center2" style="background-color: #010f33;">
            <div style="font-size: 20px">Dashboard</div>
        </div>
        <div class="container-custom-2">
            <div class="upcoming">
                <div style="display: flex; position: relative;">
                    <div style="padding: 20px; font-size: 30px;">
                        <i class="fa-solid fa-user-check" style="color: #ffffff;"></i>
                    </div>
                    <div style="position: absolute; right: 0; padding: 15px;">
                        <div style="color: #ffffff;">รายการจองของฉัน</div>
                        <div
                            style="display: flex; justify-content: flex-end; padding-top: 10px; font-size: 20px; color: rgb(136, 135, 135);">
                            <?php echo $rooms_pending; ?></div>
                    </div>
                </div>
                <hr>
                <div style="color: #ffffff; font-size: 16px; margin-left: 16px;">รอตรวจสอบ</div>
            </div>
            <div class="activing">
                <div style="display: flex; position: relative;">
                    <div style="padding: 20px; font-size: 30px;">
                        <i class="fa-solid fa-check" style="color: #ffffff;"></i>
                    </div>
                    <div style="position: absolute; right: 0; padding: 15px;">
                        <div style="color: #ffffff;">รายการจองของฉัน</div>
                        <div
                            style="display: flex; justify-content: flex-end; padding-top: 10px; font-size: 20px; color: rgb(136, 135, 135);">
                            <?php echo $rooms_approved; ?></div>
                    </div>
                </div>
                <hr>
                <div style="color: #ffffff; font-size: 16px; margin-left: 16px;">อนุมัติ</div>
            </div>
            <div class="disactiving">
                <div style="display: flex; position: relative;">
                    <div style="padding: 20px; font-size: 30px;">
                        <i class="fa-solid fa-xmark" style="color: #ffffff;"></i>
                    </div>
                    <div style="position: absolute; right: 0; padding: 15px;">
                        <div style="color: #ffffff;">รายการจองของฉัน</div>
                        <div
                            style="display: flex; justify-content: flex-end; padding-top: 10px; font-size: 20px; color: rgb(136, 135, 135);">
                            <?php echo $rooms_rejected; ?></div>
                    </div>
                </div>
                <hr>
                <div style="color: #ffffff; font-size: 16px; margin-left: 16px;">ไม่อนุมัติ</div>
            </div>

            <div class="allbooking">
                <div style="display: flex; position: relative;">
                    <div style="padding: 20px; font-size: 30px;">
                        <i class="fa-solid fa-book" style="color: #ffffff;"></i>
                    </div>
                    <div style="position: absolute; right: 0; padding: 15px;">
                        <div style="color: #ffffff;">สามารถอนุมัติได้</div>
                        <div
                            style="display: flex; justify-content: flex-end; padding-top: 10px; font-size: 20px; color: rgb(136, 135, 135);">
                            <?php echo $rooms_pending; ?></div>
                    </div>
                </div>
                <hr>
                <div style="color: #ffffff; font-size: 16px; margin-left: 16px;">รอตรวจสอบ</div>
            </div>
            <div class="allroom">
                <div style="display: flex; position: relative;">
                    <div style="padding: 20px; font-size: 30px;">
                        <i class="fa-solid fa-building" style="color: #ffffff;"></i>
                    </div>
                    <div style="position: absolute; right: 0; padding: 15px;">
                        <div style="color: #ffffff;">ห้อง</div>
                        <div
                            style="display: flex; justify-content: flex-end; padding-top: 10px; font-size: 20px; color: rgb(136, 135, 135);">
                            <?php echo $total_rooms; ?></div>
                    </div>
                </div>
                <hr>
                <div style="color: #ffffff; font-size: 16px; margin-left: 16px;">ห้องทั้งหมด</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>

    // ชื่อเดือนภาษาไทย
    const monthNames = [
        "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
        "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];

    let bookings = []; // เก็บข้อมูลการจองทั้งหมด
    let currentDate = new Date(); // เก็บเดือน-ปีปัจจุบัน

    // ฟังก์ชันดึงข้อมูลการจองจากไฟล์ get_booking.php
    async function fetchBookings() {
        try {
            const response = await fetch("get_booking.php");
            bookings = await response.json(); // แปลงข้อมูลเป็น JSON
            renderCalendar(currentDate); // สร้างปฏิทิน
        } catch (error) {
            console.error("Error fetching bookings:", error);
        }
    }

    // ฟังก์ชันสร้างปฏิทิน
    function renderCalendar(date) {
        const month = date.getMonth();
        const year = date.getFullYear();

        document.getElementById('month-year').textContent = `${monthNames[month]} ${year}`;

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const totalDays = lastDay.getDate();
        const startDay = firstDay.getDay();

        const calendarBody = document.getElementById('calendar-body');
        calendarBody.innerHTML = '';

        let row = document.createElement('tr');
        const today = new Date();

        // สร้างช่องว่างก่อนวันแรกของเดือน (ถ้าวันที่ 1 ไม่ตรงกับวันอาทิตย์)
        for (let i = 0; i < startDay; i++) {
            const cell = document.createElement('td');
            row.appendChild(cell);
        }

        // สร้างเซลล์แต่ละวันในเดือน
        for (let day = 1; day <= totalDays; day++) {
            if (row.children.length === 7) {
                calendarBody.appendChild(row);
                row = document.createElement('tr');
            }

            const cell = document.createElement('td');
            const fullDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

            // แสดงเลขวันที่
            cell.innerHTML = `<span class="day-number">${day}</span>`;

            // กรองข้อมูลการจองของวันที่นี้
            const bookingForDate = bookings.filter(booking => booking.date === fullDate);

            // เพิ่ม Event ให้คลิกได้ทุกวัน (มีหรือไม่มีการจอง)
            cell.addEventListener('click', () => showBookingDetails(bookingForDate, fullDate));

            // ถ้ามีการจอง ให้ใส่จุดสี
            if (bookingForDate.length > 0) {
                cell.classList.add('has-booking');

                // สร้าง container สำหรับจุดสี
                const dotsContainer = document.createElement('div');
                dotsContainer.classList.add('booking-dots');

                // แสดงจุดสีสำหรับแต่ละการจอง โดยใช้สีที่ตั้งไว้ในฐานข้อมูล
                bookingForDate.forEach(booking => {
                    const dot = document.createElement('div');
                    dot.classList.add('booking-dot'); // กำหนดรูปทรงและขนาด dot ผ่าน CSS
                    dot.style.backgroundColor = booking.color; // ใช้สีจากฐานข้อมูล (key "color")
                    dotsContainer.appendChild(dot);
                });
                cell.appendChild(dotsContainer);
            }

            // ไฮไลต์วันที่ปัจจุบัน
            if (
                day === today.getDate() &&
                month === today.getMonth() &&
                year === today.getFullYear()
            ) {
                cell.classList.add('current-day');
            }

            row.appendChild(cell);
        }

        if (row.children.length > 0) {
            calendarBody.appendChild(row);
        }
    }

    // ฟังก์ชันแสดงรายละเอียดการจองใน Modal
    function showBookingDetails(bookingsForDate, date) {
        const modalBody = document.getElementById('bookingModalBody');
        const modalTitle = document.getElementById('bookingModalLabel');

        if (bookingsForDate.length > 0) {
            modalTitle.textContent = `รายละเอียดการจอง (${date})`;

            let tablesHtml = bookingsForDate.map((booking, index) => {
                // สร้าง dotHtml จากสีในฐานข้อมูล (booking.color)
                const dotHtml = `
        <span 
            style="
                display: inline-block; 
                width: 12px; 
                height: 12px; 
                border-radius: 50%; 
                background-color: ${booking.color || '#ccc'}; 
                margin-right: 8px;
            ">
        </span>
    `;

                return `
        <table class="table table-striped table-bordered" style="margin-bottom: 20px; table-layout: auto; width: 100%;">
            <thead>
                <tr>
                    <th colspan="2" style="text-align: center;">
                        <!-- แทรก dot ไว้ด้านหน้าข้อความ -->
                        ${dotHtml}การจองครั้งที่ ${index + 1}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th style="text-align: left; vertical-align: top;">ผู้จอง</th>
                    <td style="text-align: left; vertical-align: top;">${booking.booker_name || 'ไม่ระบุ'}</td>
                </tr>
                <tr>
                    <th style="text-align: left; vertical-align: top;">เบอร์ผู้จอง</th>
                    <td style="text-align: left; vertical-align: top;">${booking.booker_phone || '-'}</td>
                </tr>
                <tr>
                    <th style="text-align: left; vertical-align: top;">ชื่อห้อง</th>
                    <td style="text-align: left; vertical-align: top;">${booking.room_name || 'ไม่ระบุ'}</td>
                </tr>
                <tr>
                    <th style="text-align: left; vertical-align: top;">ช่วงเวลาที่จอง</th>
                    <td style="text-align: left; vertical-align: top;">${booking.booking_time || '-'}</td>
                </tr>
                <tr>
                    <th style="text-align: left; vertical-align: top;">รายละเอียด</th>
                    <td style="text-align: left; vertical-align: top;">${booking.details || '-'}</td>
                </tr>
            </tbody>
        </table>
    `;
            }).join('');


            modalBody.innerHTML = tablesHtml;
        } else {
            modalTitle.textContent = `ไม่มีรายการจอง (${date})`;
            modalBody.innerHTML = `<p style="text-align: center; color: red;">ไม่มีการจองในวันนี้ :)</p>`;
        }

        // เปิด Modal
        const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
        bookingModal.show();
    }

    // ปุ่มเปลี่ยนเดือน (ก่อนหน้า)
    document.getElementById('prev-month').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
    });

    // ปุ่มเปลี่ยนเดือน (ถัดไป)
    document.getElementById('next-month').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
    });

    // ดึงข้อมูลจากฐานข้อมูลครั้งแรก เมื่อโหลดหน้าเสร็จ
    fetchBookings();
    </script>












</body>

</html>