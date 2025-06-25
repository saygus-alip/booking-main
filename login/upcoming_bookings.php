<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

include 'db_connect.php';
include 'auth_check.php';

// กำหนด SQL เบื้องต้น
$sql = "SELECT 
            b.Booking_ID, 
            b.Topic_Name, 
            h.Hall_Name, 
            CONCAT(p.First_Name, ' ', p.Last_Name) AS Booker_Name, 
            b.Date_Start, 
            b.Time_Start, 
            b.Time_End, 
            b.Attendee_Count, 
            b.Personnel_ID AS personnel_id,  
            s.Status_Name
        FROM 
            booking b
        LEFT JOIN personnel p ON b.Personnel_ID = p.Personnel_ID
        LEFT JOIN hall h ON b.Hall_ID = h.Hall_ID
        LEFT JOIN booking_status s ON b.Status_ID = s.Status_ID
        WHERE b.Status_ID = 1";

// หากไม่ใช่แอดมิน (เช่น role_id ไม่เท่ากับ 2) ให้แสดงเฉพาะรายการของตัวเอง
if ($_SESSION['role_id'] != 2) {
    $sql .= " AND b.Personnel_ID = '" . $_SESSION['personnel_id'] . "'";
}

$sql .= " ORDER BY b.Booking_ID DESC";

$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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

    main {
        flex-grow: 1;
        /* ให้ส่วนเนื้อหาขยายเต็มพื้นที่ที่เหลือ */
        overflow: auto;
        padding-bottom: 20px;
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
        background-color:rgb(1, 20, 69);
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

    .footer {
        width: 100%;
        background-color: #f8f9fa;
        padding: 20px;
        font-size: 16px;
        color: #6c757d;
        margin-top: auto;
        position: relative;
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
            <div style="font-size: 20px">รอตรวจสอบ</div>
        </div>
        <div class="container-custom">
            <?php
                if(isset($_SESSION['message'])){
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                }
            ?>
            <div class="container mt-5">
                <?php
            // ตรวจสอบว่ามีข้อมูลหรือไม่ ถ้าไม่มีให้กำหนดให้ซ่อนตาราง
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
                            <th>จำนวนผู้เข้าร่วม</th>
                            <th>สถานะ</th>
                            <th>เหตุผล</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
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
                                <?php if ($row['Status_Name'] == 'รอตรวจสอบ'): ?>
                                <span class="text-warning">รอตรวจสอบ</span>
                                <?php elseif ($row['Status_Name'] == 'อนุมัติ'): ?>
                                <span class="text-success">อนุมัติ</span>
                                <?php elseif ($row['Status_Name'] == 'ไม่อนุมัติ'): ?>
                                <span class="text-danger">ไม่อนุมัติ</span>
                                <?php else: ?>
                                <span class="text-muted">ไม่ทราบสถานะ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-outline-dark btn-sm detail-btn"
                                    data-id="<?php echo $row['Booking_ID']; ?>">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <?php if (isset($_SESSION['personnel_id']) && $_SESSION['personnel_id'] == $row['personnel_id']): ?>
                                <a href="delete_booking_upcom.php?id=<?php echo $row['Booking_ID']; ?>"
                                    class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('คุณแน่ใจหรือไม่ว่าจะลบการจองนี้?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>

                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรายละเอียดห้อง -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="detailModalLabel">รายละเอียดห้อง</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- เนื้อหาจะถูกโหลดมาจาก room_detail_upcom.php ผ่าน AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>




    <!-- JavaScript -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.bootstrap5.js"></script>
    <script>
    $(document).ready(function() {
        $('#member-table').DataTable(); // ใช้ DataTables แบบพื้นฐาน
    });

    $(document).ready(function() {
        $('.detail-btn').on('click', function() {
            var bookingId = $(this).data('id');
            // แสดงข้อความกำลังโหลดใน modal body
            $('#detailModal .modal-body').html('Loading...');
            // ทำการ AJAX request ไปยัง room_detail_upcom.php โดยส่ง booking id
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
                    $('#detailModal .modal-body').html('Error loading details.');
                }
            });
            // แสดง modal dialog
            var modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();
        });
    });
    </script>



</body>

</html>