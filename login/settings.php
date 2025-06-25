<?php 
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

include 'db_connect.php';
include 'auth_check.php';

// ดึงข้อมูลการจองห้องประชุมในแต่ละวันของเดือนปัจจุบัน
$sql = "SELECT DAY(Date_Start) AS Day, COUNT(*) AS TotalBookings
        FROM booking
        WHERE MONTH(Date_Start) = MONTH(CURDATE()) AND YEAR(Date_Start) = YEAR(CURDATE())
        GROUP BY DAY(Date_Start)
        ORDER BY DAY(Date_Start)";

$result = $conn->query($sql);

$days = [];
$totalBookings = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $days[] = $row['Day'];  // เก็บวันที่
        $totalBookings[] = $row['TotalBookings'];  // เก็บจำนวนการจอง
    }
} else {
    // ถ้าไม่มีข้อมูลใดๆ ก็ให้ค่าเริ่มต้นเป็น array ว่าง
    $days = [];
    $totalBookings = [];
}

// กราฟแท่งสำหรับการจองห้องประชุม
$thaiMonths = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
$daysInMonth = [];
$totalBookingsForGraph = [];

// ถ้าหากมีข้อมูลจากฐานข้อมูลแล้ว
if (count($days) > 0) {
    foreach ($days as $day) {
        // แปลงวันที่ให้เป็น "วัน เดือน ปี พ.ศ."
        $thaiDate = $day . " " . $thaiMonths[date('n')-1] . " " . (date('Y')+543); // ปรับเป็น พ.ศ.
        $daysInMonth[] = $thaiDate;
       
    }
    
    // ข้อมูลการจองห้องประชุมจากฐานข้อมูล
    $totalBookingsForGraph = $totalBookings;
} else {
    // กรณีไม่มีข้อมูลการจองในบางวัน
    $daysInMonth = []; // หรือคุณอาจแสดงวันที่ในเดือนนี้ทั้งหมด
    $totalBookingsForGraph = [];
}

// ดึงข้อมูลผู้ใช้งานทั้งหมด
$sql_total_users = "SELECT COUNT(*) AS total_users FROM personnel";
$result_total_users = $conn->query($sql_total_users);
$row_total_users = $result_total_users->fetch_assoc();
$total_users = $row_total_users['total_users'];

// ดึงข้อมูลผู้ใช้งานที่ลงทะเบียนใน 30 วันล่าสุด
$sql_new_users = "SELECT COUNT(*) AS new_users FROM personnel WHERE created_at >= CURDATE() - INTERVAL 30 DAY";
$result_new_users = $conn->query($sql_new_users);
$row_new_users = $result_new_users->fetch_assoc();
$new_users = $row_new_users['new_users'];

// ดึงข้อมูลผู้ใช้งานที่กำลังใช้ระบบ
$sql_active_users = "SELECT COUNT(*) AS active_users FROM personnel WHERE last_login >= CURDATE() - INTERVAL 1 DAY";
$result_active_users = $conn->query($sql_active_users);
$row_active_users = $result_active_users->fetch_assoc();
$active_users = $row_active_users['active_users'];

// ส่งข้อมูลไปยัง JavaScript
echo "<script>
    var totalUsers = " . json_encode($total_users) . ";
    var newUsers = " . json_encode($new_users) . ";
    var activeUsers = " . json_encode($active_users) . ";
</script>";

// ดึงข้อมูลห้องประชุม
$sql = "SELECT Hall_ID, Hall_Name, Capacity FROM hall";
$result = $conn->query($sql);

$hallNames = [];
$capacity = [];
$totalBookings = [];
$totalUsageTimes = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hallNames[] = $row['Hall_Name'];  // ชื่อห้อง
        $capacity[] = $row['Capacity'];    // ความจุของห้อง

        // ดึงข้อมูลจำนวนการจองและเวลาการใช้งาน
        $hall_id = $row['Hall_ID'];
        
        // ดึงจำนวนการจอง
        $bookingSql = "SELECT COUNT(*) AS TotalBookings FROM booking WHERE Hall_ID = ?";
        $stmt = $conn->prepare($bookingSql);
        $stmt->bind_param("i", $hall_id);
        $stmt->execute();
        $bookingResult = $stmt->get_result();
        $totalBookings[] = $bookingResult->fetch_assoc()['TotalBookings'];
        
        // ดึงเวลาการใช้งานทั้งหมด (ในชั่วโมง)
        $usageSql = "SELECT SUM(TIMESTAMPDIFF(HOUR, Time_Start, Time_End)) AS TotalUsageTime FROM booking WHERE Hall_ID = ?";
        $stmt = $conn->prepare($usageSql);
        $stmt->bind_param("i", $hall_id);
        $stmt->execute();
        $usageResult = $stmt->get_result();
        $totalUsageTimes[] = $usageResult->fetch_assoc()['TotalUsageTime'];
    }
} else {
    $hallNames = [];
    $totalBookings = [];
    $totalUsageTimes = [];
}

// ดึงข้อมูลจำนวนการจองในช่วงเช้า, บ่าย และกลางคืน
$sql = "SELECT 
            SUM(CASE WHEN TIME(Time_Start) BETWEEN '08:00:00' AND '12:00:00' THEN 1 ELSE 0 END) AS MorningBookings,
            SUM(CASE WHEN TIME(Time_Start) BETWEEN '12:01:00' AND '18:00:00' THEN 1 ELSE 0 END) AS AfternoonBookings,
            SUM(CASE WHEN TIME(Time_Start) BETWEEN '18:01:00' AND '23:59:59' THEN 1 ELSE 0 END) AS NightBookings
        FROM booking
        WHERE MONTH(Date_Start) = MONTH(CURDATE()) AND YEAR(Date_Start) = YEAR(CURDATE())";

$result = $conn->query($sql);

$morningBookings = 0;
$afternoonBookings = 0;
$nightBookings = 0;

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $morningBookings = $row['MorningBookings'];
    $afternoonBookings = $row['AfternoonBookings'];
    $nightBookings = $row['NightBookings'];
}

// ดึงข้อมูลจำนวนการจองห้องประชุมในแต่ละเดือนของปีปัจจุบัน
$sql = "SELECT MONTH(Date_Start) AS Month, COUNT(*) AS TotalBookings
        FROM booking
        WHERE YEAR(Date_Start) = YEAR(CURDATE())
        GROUP BY MONTH(Date_Start)
        ORDER BY MONTH(Date_Start)";

$result = $conn->query($sql);

$months = [];
$totalBookingsYear = [];

// ตั้งค่าเดือนภาษาไทย
$thaiMonths = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];

if ($result->num_rows > 0) {
    // ดึงข้อมูลเดือนและจำนวนการจอง
    while ($row = $result->fetch_assoc()) {
        $months[] = $thaiMonths[$row['Month'] - 1];  // แปลงหมายเลขเดือนเป็นชื่อเดือนภาษาไทย
        $totalBookingsYear[] = $row['TotalBookings'];  // เก็บจำนวนการจอง
    }
} else {
    // กรณีไม่มีข้อมูลการจอง
    $months = $thaiMonths;  // ใช้ชื่อเดือนทั้ง 12 เดือน
    $totalBookingsYear = array_fill(0, 12, 0);  // จำนวนการจองเป็น 0
}

// ดึงข้อมูลการจองห้องประชุมล่าสุด โดยกรองสถานะที่เป็น "อนุมัติ" และ "ไม่อนุมัติ"
$sql = "SELECT b.Booking_ID, b.Topic_Name, h.Hall_Name, p.first_name AS Booked_By, 
               b.Date_Start, b.Time_Start, b.Time_End, b.Attendee_Count, 
               bs.Status_Name AS Status
        FROM booking b
        JOIN hall h ON b.Hall_ID = h.Hall_ID
        JOIN personnel p ON b.Personnel_ID = p.Personnel_ID
        JOIN booking_status bs ON b.Status_ID = bs.Status_ID
        WHERE b.Date_Start >= CURDATE() 
        AND (bs.Status_Name = 'อนุมัติ' OR bs.Status_Name = 'ไม่อนุมัติ')
        ORDER BY b.Date_Start DESC, b.Time_Start DESC";

$result = $conn->query($sql);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติ</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.bootstrap5.css">
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

    main {
        flex-grow: 1;
        /* ให้ส่วนเนื้อหาขยายเต็มพื้นที่ที่เหลือ */
        overflow: auto;
        padding-bottom: 20px;
    }

    /* จัดข้อความให้อยู่กึ่งกลางทั้งแนวตั้งและแนวนอน */
    .table td,
    .table th {
        /* Padding ด้านล่าง */
        text-align: center;
        /* จัดกึ่งกลางแนวนอน */
        vertical-align: middle;
        /* จัดกึ่งกลางแนวตั้ง */
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

    .container-custom {
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        max-width: 1200px;
        width: 100%;
        margin-bottom: 20px;
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
        background-color: #010f33;
        color: #ffffff;
    }

    .dropdown-menu .dropdown-item {
        color: #ffffff;
    }

    .dropdown-menu .dropdown-item:hover {
        background-color: #e0a444;
        color: #ffffff;
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

    .btn-outline.active {
        background-color: #007bff;
        color: white;
    }

    .stat-content {
        display: none;
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
            <div style="font-size: 20px">สถิติ</div>
        </div>
        <div class="container-custom">
            <div style="display: flex;">
                <div class="m-2">
                    <button id="stat1-btn" class="btn btn-outline-dark">การจองห้องประชุม</button>
                </div>
                <div class="m-2">
                    <button id="stat2-btn" class="btn btn-outline-dark">ผู้ใช้งาน</button>
                </div>
                <div class="m-2">
                    <button id="stat3-btn" class="btn btn-outline-dark">ห้องประชุม</button>
                </div>
                <div class="m-2">
                    <button id="stat4-btn" class="btn btn-outline-dark">การใช้งานตามเวลา</button>
                </div>
                <div class="m-2">
                    <button id="stat5-btn" class="btn btn-outline-dark">การจองห้องในหนึ่งปี</button>
                </div>
                <div class="m-2">
                    <button id="stat6-btn" class="btn btn-outline-dark">รายงานกิจกรรมล่าสุด</button>
                </div>
            </div>

            <div id="stat1-content" class="stat-content"
                style="padding: 40px; padding-right: 80px; padding-left: 80px;">
                <!-- กราฟแท่ง (Bar Chart) -->
                <canvas id="barChart1" width="800" height="400"></canvas>
            </div>

            <div id="stat2-content" class="stat-content"
                style="padding: 40px; padding-right: 80px; padding-left: 80px;">
                <!-- กราฟแท่ง (Bar Chart) -->
                <canvas id="barChart2" width="800" height="400"></canvas>
            </div>

            <div id="stat3-content" class="stat-content"
                style="padding: 40px; padding-right: 80px; padding-left: 80px;">
                <!-- กราฟแท่ง (Bar Chart) -->
                <canvas id="barChart3" width="800" height="400"></canvas>
            </div>

            <div id="stat4-content" class="stat-content"
                style="padding: 40px; padding-right: 80px; padding-left: 80px;">
                <!-- กราฟแท่ง (Bar Chart) -->
                <canvas id="barChart4" width="800" height="400"></canvas>
            </div>

            <div id="stat5-content" class="stat-content"
                style="padding: 40px; padding-right: 80px; padding-left: 80px;">
                <!-- กราฟแท่ง (Bar Chart) -->
                <canvas id="barChart5" width="800" height="400"></canvas>
            </div>

            <div id="stat6-content" class="stat-content" style="padding: 20px;">
                <table id="member-table" class="table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>หัวข้อ</th>
                            <th>ชื่อห้อง</th>
                            <th>ชื่อผู้จอง</th>
                            <th>วันที่และเวลา</th>
                            <th>จำนวนผู้เข้าร่วม</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // ตรวจสอบว่ามีข้อมูลหรือไม่
                    if ($result->num_rows > 0) {
                        $row_num = 1;
                        while ($row = $result->fetch_assoc()) {
                            // กำหนดสีตามสถานะ
                            $status_class = '';
                            if ($row['Status'] == 'อนุมัติ') {
                                $status_class = 'text-success';  // สีเขียวสำหรับอนุมัติ
                            } else {
                                $status_class = 'text-danger';  // สีแดงสำหรับไม่อนุมัติ
                            }

                            echo "<tr>
                                    <td>" . $row_num++ . "</td>
                                    <td>" . htmlspecialchars($row['Topic_Name']) . "</td>
                                    <td>" . htmlspecialchars($row['Hall_Name']) . "</td>
                                    <td>" . htmlspecialchars($row['Booked_By']) . "</td>
                                    <td>" . $row['Date_Start'] . " " . $row['Time_Start'] . " - " . $row['Time_End'] . "</td>
                                    <td>" . $row['Attendee_Count'] . "</td>
                                    <td class='$status_class'>" . $row['Status'] . "</td>
                                </tr>";
                        }
                    } else {
                        // ซ่อนตารางโดยใช้ CSS
                        echo "<script>document.getElementById('member-table').style.display = 'none';</script>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- JavaScript -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.bootstrap5.js"></script>

    <script>
    $(document).ready(function() {
        $('#member-table').dataTable();
        // แสดง stat1 เมื่อเข้ามาครั้งแรก
        $('#stat1-content').show(); // แสดง div ของ stat1
        $('#stat1-btn').addClass('btn-dark active'); // ทำให้ปุ่ม stat1 ถูกเลือก

        // เมื่อกดปุ่มสถิติการจองห้องประชุม
        $('#stat1-btn').click(function() {
            $('.stat-content').hide(); // ซ่อนทุก div ของสถิติ
            $('#stat1-content').show(); // แสดง div ของ stat1
            // เปลี่ยนสถานะ active ของปุ่ม
            $('#stat1-btn').removeClass('btn-outline-dark').addClass('btn-dark active');
            $('#stat2-btn, #stat3-btn, #stat4-btn, #stat5-btn, #stat6-btn').removeClass(
                'btn-dark active').addClass('btn-outline-dark');
        });

        // เมื่อกดปุ่มสถิติผู้ใช้งาน
        $('#stat2-btn').click(function() {
            $('.stat-content').hide(); // ซ่อนทุก div ของสถิติ
            $('#stat2-content').show(); // แสดง div ของ stat2
            // เปลี่ยนสถานะ active ของปุ่ม
            $('#stat2-btn').removeClass('btn-outline-dark').addClass('btn-dark active');
            $('#stat1-btn, #stat3-btn, #stat4-btn, #stat5-btn, #stat6-btn').removeClass(
                'btn-dark active').addClass('btn-outline-dark');
        });

        // เมื่อกดปุ่มสถิติห้องประชุม
        $('#stat3-btn').click(function() {
            $('.stat-content').hide(); // ซ่อนทุก div ของสถิติ
            $('#stat3-content').show(); // แสดง div ของ stat3
            // เปลี่ยนสถานะ active ของปุ่ม
            $('#stat3-btn').removeClass('btn-outline-dark').addClass('btn-dark active');
            $('#stat1-btn, #stat2-btn, #stat4-btn, #stat5-btn, #stat6-btn').removeClass(
                'btn-dark active').addClass('btn-outline-dark');
        });

        // เมื่อกดปุ่มสถิติการใช้งานตามเวลา
        $('#stat4-btn').click(function() {
            $('.stat-content').hide(); // ซ่อนทุก div ของสถิติ
            $('#stat4-content').show(); // แสดง div ของ stat4
            // เปลี่ยนสถานะ active ของปุ่ม
            $('#stat4-btn').removeClass('btn-outline-dark').addClass('btn-dark active');
            $('#stat1-btn, #stat2-btn, #stat3-btn, #stat5-btn, #stat6-btn').removeClass(
                'btn-dark active').addClass('btn-outline-dark');
        });

        // เมื่อกดปุ่มสรุปสถิติการจองห้องหมดในหนึ่งปี
        $('#stat5-btn').click(function() {
            $('.stat-content').hide(); // ซ่อนทุก div ของสถิติ
            $('#stat5-content').show(); // แสดง div ของ stat5
            // เปลี่ยนสถานะ active ของปุ่ม
            $('#stat5-btn').removeClass('btn-outline-dark').addClass('btn-dark active');
            $('#stat1-btn, #stat2-btn, #stat3-btn, #stat4-btn, #stat6-btn').removeClass(
                'btn-dark active').addClass('btn-outline-dark');
        });

        // เมื่อกดปุ่มรายงานกิจกรรมล่าสุด
        $('#stat6-btn').click(function() {
            $('.stat-content').hide(); // ซ่อนทุก div ของสถิติ
            $('#stat6-content').show(); // แสดง div ของ stat6
            // เปลี่ยนสถานะ active ของปุ่ม
            $('#stat6-btn').removeClass('btn-outline-dark').addClass('btn-dark active');
            $('#stat1-btn, #stat2-btn, #stat3-btn, #stat4-btn, #stat5-btn').removeClass(
                'btn-dark active').addClass('btn-outline-dark');
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    var days = <?php echo json_encode($daysInMonth); ?>;
    var totalBookings = <?php echo json_encode($totalBookingsForGraph); ?>;

    // กราฟแท่งสำหรับการจองห้องประชุม
    var ctxBar = document.getElementById('barChart1').getContext('2d');
    var barChart = new Chart(ctxBar, {
        type: 'bar', // ประเภทของกราฟ
        data: {
            labels: days, // ใช้วันที่ที่ได้จาก PHP
            datasets: [{
                label: 'จำนวนการจองห้องประชุม',
                data: totalBookings, // ใช้จำนวนการจองที่ดึงมาแสดงเป็นค่า Y
                backgroundColor: 'rgba(54, 162, 235, 0.2)', // สีพื้นหลังแท่ง
                borderColor: 'rgba(54, 162, 235, 1)', // สีขอบแท่ง
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true, // เริ่มต้นจาก 0
                    suggestedMin: 0, // กำหนดค่าต่ำสุดของแกน Y (เพิ่มช่องว่างด้านล่าง)
                    suggestedMax: Math.max(...totalBookings) + 10, // เพิ่มช่องว่างด้านบน
                    ticks: {
                        font: {
                            size: 16, // ขนาดฟอนต์ของแกน Y
                        },
                        stepSize: 1, // กำหนดให้ไม่แสดงทศนิยม
                        precision: 0 // ลบทศนิยมในค่า Y
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 16, // ขนาดฟอนต์ของแกน X
                        },
                        // ให้ใช้วันที่ที่แสดงใน labels
                        callback: function(value, index, values) {
                            return days[index]; // แสดง label ในรูปแบบที่เรากำหนด
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    titleFont: {
                        size: 14 // ขนาดฟอนต์ของ tooltip
                    },
                    bodyFont: {
                        size: 12 // ขนาดฟอนต์ของข้อความใน tooltip
                    }
                },
                legend: {
                    labels: {
                        font: {
                            size: 16, // ขนาดฟอนต์ของ legend
                        }
                    }
                }
            }
        }
    });

    // ข้อมูลที่ส่งมาจาก PHP
    var totalUsers = <?php echo json_encode($total_users); ?>;
    var newUsers = <?php echo json_encode($new_users); ?>;
    var activeUsers = <?php echo json_encode($active_users); ?>;

    // กราฟแท่งสำหรับผู้ใช้งาน
    var ctxBar2 = document.getElementById('barChart2').getContext('2d');
    var barChart2 = new Chart(ctxBar2, {
        type: 'bar', // ประเภทกราฟแท่ง
        data: {
            labels: ['ผู้ใช้งานทั้งหมด', 'ผู้ใช้งานใหม่ (30 วัน)', 'ผู้ใช้งานที่กำลังใช้ระบบ'], // ป้ายชื่อแกน X
            datasets: [{
                label: 'จำนวนผู้ใช้งาน',
                data: [totalUsers, newUsers, activeUsers], // ข้อมูลจำนวนผู้ใช้งานที่ได้จาก PHP
                backgroundColor: 'rgba(255, 99, 132, 0.2)', // สีพื้นหลังแท่ง
                borderColor: 'rgba(255, 99, 132, 1)', // สีขอบแท่ง
                borderWidth: 1
            }]
        },
        options: {
            responsive: true, // ทำให้กราฟสามารถปรับขนาดได้ตามขนาดของหน้าจอ
            scales: {
                y: {
                    beginAtZero: true, // เริ่มจากค่า 0
                    suggestedMin: 0, // กำหนดค่าต่ำสุดของแกน Y (เพิ่มช่องว่างด้านล่าง)
                    suggestedMax: Math.max(...totalBookings) + 10, // เพิ่มช่องว่างด้านบน
                    ticks: {
                        font: {
                            size: 16 // ขนาดฟอนต์ของแกน Y  
                        },
                        stepSize: 1, // กำหนดให้ไม่แสดงทศนิยม
                        precision: 0 // ลบทศนิยมในค่า Y
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 16 // ขนาดฟอนต์ของแกน X
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    titleFont: {
                        size: 14 // ขนาดฟอนต์ของ title ใน tooltip
                    },
                    bodyFont: {
                        size: 12 // ขนาดฟอนต์ของข้อมูลใน tooltip
                    }
                },
                legend: {
                    labels: {
                        font: {
                            size: 16 // ขนาดฟอนต์ของ legend
                        }
                    }
                }
            }
        }
    });

    var hallNames = <?php echo json_encode($hallNames); ?>;
    var totalBookings = <?php echo json_encode($totalBookings); ?>;
    var totalUsageTimes = <?php echo json_encode($totalUsageTimes); ?>;

    // กราฟแท่งสำหรับห้องประชุม
    var ctxBar3 = document.getElementById('barChart3').getContext('2d');
    var barChart3 = new Chart(ctxBar3, {
        type: 'bar',
        data: {
            labels: hallNames, // ใช้ชื่อห้องเป็น label
            datasets: [{
                label: 'จำนวนการจองห้องประชุม',
                data: totalBookings, // จำนวนการจองห้องประชุม
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }, {
                label: 'เวลาการใช้งานทั้งหมด (ชั่วโมง)',
                data: totalUsageTimes, // เวลาการใช้งานทั้งหมด
                backgroundColor: 'rgba(21, 75, 75, 0.2)',
                borderColor: 'rgb(13, 119, 119)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true, // เริ่มต้นจาก 0
                    suggestedMin: 0, // กำหนดค่าต่ำสุดของแกน Y (เพิ่มช่องว่างด้านล่าง)
                    suggestedMax: Math.max(...totalBookings, ...totalUsageTimes) + 10, // เพิ่มช่องว่างด้านบน
                    ticks: {
                        font: {
                            size: 16
                        },
                        stepSize: 1, // กำหนดให้ไม่แสดงทศนิยม
                        precision: 0 // ลบทศนิยมในค่า Y
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 12
                    }
                },
                legend: {
                    labels: {
                        font: {
                            size: 16
                        }
                    }
                }
            }
        }
    });

    // ข้อมูลการจองห้องประชุมตามช่วงเวลา
    var morningBookings = <?php echo json_encode($morningBookings); ?>;
    var afternoonBookings = <?php echo json_encode($afternoonBookings); ?>;
    var nightBookings = <?php echo json_encode($nightBookings); ?>;

    // กราฟแท่งสำหรับการใช้งานตามเวลา
    var ctxBar4 = document.getElementById('barChart4').getContext('2d');
    var barChart4 = new Chart(ctxBar4, {
        type: 'bar',
        data: {
            labels: ['ช่วงเช้า', 'ช่วงบ่าย', 'ช่วงเย็น'],
            datasets: [{
                label: 'จำนวนการจองห้องประชุม',
                data: [morningBookings, afternoonBookings, nightBookings], // ข้อมูล
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true, // เริ่มต้นจาก 0
                    suggestedMin: 0, // กำหนดค่าต่ำสุดของแกน Y (เพิ่มช่องว่างด้านล่าง)
                    suggestedMax: Math.max(...totalBookings, ...totalUsageTimes) + 10, // เพิ่มช่องว่างด้านบน
                    ticks: {
                        font: {
                            size: 16
                        },
                        stepSize: 1, // กำหนดให้ไม่แสดงทศนิยม
                        precision: 0 // ลบทศนิยมในค่า Y
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 12
                    }
                },
                legend: {
                    labels: {
                        font: {
                            size: 16
                        }
                    }
                }
            }
        }
    });

    // ข้อมูลจำนวนการจองห้องประชุมใน 1 ปี
    var months = <?php echo json_encode($months); ?>;
    var totalBookingsYear = <?php echo json_encode($totalBookingsYear); ?>;

    // กราฟแท่งสำหรับการจองห้องในหนึ่งปี
    var ctxBar5 = document.getElementById('barChart5').getContext('2d');
    var barChart5 = new Chart(ctxBar5, {
        type: 'bar',
        data: {
            labels: months, // ป้ายชื่อแกน X (เดือน)
            datasets: [{
                label: 'จำนวนการจองห้องประชุมใน 1 ปี',
                data: totalBookingsYear, // จำนวนการจองที่ดึงมาแสดง
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true, // เริ่มต้นจาก 0
                    suggestedMin: 0, // กำหนดค่าต่ำสุดของแกน Y (เพิ่มช่องว่างด้านล่าง)
                    suggestedMax: Math.max(...totalBookings, ...totalUsageTimes) + 10, // เพิ่มช่องว่างด้านบน
                    ticks: {
                        font: {
                            size: 16
                        },
                        stepSize: 1, // กำหนดให้ไม่แสดงทศนิยม
                        precision: 0 // ลบทศนิยมในค่า Y
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 12
                    }
                },
                legend: {
                    labels: {
                        font: {
                            size: 16
                        }
                    }
                }
            }
        }
    });
    </script>



</body>

</html>