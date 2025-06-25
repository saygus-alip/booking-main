<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// หากล็อกอินแล้ว จะแสดงข้อมูลสมาชิกได้
include 'db_connect.php';
include 'auth_check.php'; // เรียกใช้งานการตรวจสอบการเข้าสู่ระบบและสถานะผู้ใช้

// ดึงข้อมูลของผู้ใช้ที่ล็อกอินจากตาราง personnel
$personnel_id = $_SESSION['personnel_id'];
$sql = "SELECT First_Name, Last_Name, Email, Phone, Telegram_ID, Position_ID, Subject_Group_ID, Role_ID FROM personnel WHERE Personnel_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $personnel_id);
$stmt->execute();
$stmt->bind_result($first_name, $last_name, $email, $phone, $telegram_id, $position_id, $subject_group_id, $role_id);
$stmt->fetch();
$stmt->close();

// ดึงข้อมูลสมาชิกที่ต้องการแก้ไข
if (isset($_GET['id'])) {
    $personnel_id = $_GET['id'];
    $sql = "SELECT * FROM personnel WHERE personnel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $personnel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมาชิก</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

    .table td,
    .table th {
        padding-top: 15px;  /* Padding ด้านบน */
        padding-bottom: 15px; /* Padding ด้านล่าง */
        text-align: center;
        /* จัดกึ่งกลางแนวนอน */
        vertical-align: middle;
        /* จัดกึ่งกลางแนวตั้ง */
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

    .button-container {
        display: flex;
        justify-content: flex-end; /* ทำให้ปุ่มไปอยู่ทางขวา */
        width: 100%;
    }

    .add-user {
        display: flex;
        justify-content: center;
        align-items: center;
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
            <div style="font-size: 20px; width: 100%; text-align: left;">รายการสมาชิก</div>
            <div class="button-container">
                <button class="add-user btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <div style="font-size: 15px;">เพิ่มผู้ใช้งาน</div>
                </button>
            </div>
        </div>
        <div class="container-custom">
            <!-- แสดงข้อความที่นี่ -->
            <?php
            if (isset($_SESSION['message'])) {
                echo $_SESSION['message'];
                unset($_SESSION['message']);
            }
            ?>
            <table id="member-table" class="table table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ชื่อ (Firstname)</th>
                        <th>นามสกุล (Lastname)</th>
                        <th>ชื่อผู้ใช้ (Username)</th>
                        <th>ตำแหน่ง (Position)</th>
                        <th>สถานะ (Role)</th>
                        <th>เหตุผล (Reason)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // ดึงข้อมูลสมาชิกจากฐานข้อมูล
                    $sql = "SELECT personnel_id, first_name, last_name, username, position_id, role_id, subject_group_id, phone, email, telegram_id, password FROM personnel";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        // ถ้ามีข้อมูลสมาชิกให้แสดงข้อมูลในตาราง
                        $count = 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $count++ . "</td>";
                            echo "<td>" . $row['first_name'] . "</td>";
                            echo "<td>" . $row['last_name'] . "</td>";
                            echo "<td>" . $row['username'] . "</td>";
                            // กำหนดตำแหน่งเป็นข้อความที่เข้าใจง่าย
                            $position_id = "";
                            switch ($row['position_id']) {
                                case 1:
                                    $position_id = "ผู้บริหาร";
                                    break;
                                case 2:
                                    $position_id = "ครู";
                                    break;
                                case 3:
                                    $position_id = "บุคคาลากร";
                                    break;
                            }
                            echo "<td>" . $position_id . "</td>";

                            $role_id = "";
                            switch ($row['role_id']) {
                                case 1:
                                    $role_id = "ผู้ใช้";
                                    break;
                                case 2:
                                    $role_id = "Admin";
                                    break;
                                case 3:
                                    $role_id = "ผู้อนุมัติ";
                                    break;
                                case 4:
                                    $role_id = "รอง";
                                    break;
                            }
                            echo "<td>" . $role_id . "</td>";
                            // เพิ่มลิงก์แก้ไขในตาราง
                            echo "<td>
                                <button type='button' class='btn btn-outline-warning btn-sm editBtn' 
                                      data-bs-toggle='modal' 
                                      data-bs-target='#editUserModal'
                                      data-id='" . $row['personnel_id'] . "'
                                      data-first_name='" . $row['first_name'] . "'
                                      data-last_name='" . $row['last_name'] . "'
                                      data-username='" . $row['username'] . "'
                                      data-position='" . $row['position_id'] . "'
                                      data-phone='" . $row['phone'] . "'
                                      data-subject_group='" . $row['subject_group_id'] . "'
                                      data-role='" . $row['role_id'] . "'
                                      data-telegram='" . $row['telegram_id'] . "'
                                      data-email='" . $row['email'] . "'>
                                      
                                      <i class='fas fa-edit'></i> แก้ไข
                                </button>
                                <a href='delete_member.php?id=" . $row['personnel_id'] . "' class='btn btn-outline-danger btn-sm' onclick='return confirm(\"คุณแน่ใจว่าต้องการลบผู้ใช้งานนี้?\")'>ลบ</a>
                            </td>";

                            echo "</tr>";
                        }            
                    } else {
                        // ถ้าไม่มีข้อมูลสมาชิกให้แสดงข้อความแจ้ง
                        echo "<tr><td colspan='5'>ไม่พบข้อมูลสมาชิก</td></tr>";
                    }

                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal สำหรับเพิ่มผู้ใช้งาน -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">เพิ่มผู้ใช้งานใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="add_user.php" method="post">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้ (ห้ามซ้ำ)</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">สถานะ</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="1">ผู้ใช้</option>
                                <option value="2">Admin</option>
                                <option value="3">ผู้อนุมัติ</option>
                                <option value="4">รอง</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subject_group_id" class="form-label">กลุ่มสาระการเรียนรู้</label>
                            <select name="subject_group_id" id="subject_group_id" class="form-select" required>
                                <option value="1">กลุ่มสาระการเรียนรู้ภาษาไทย</option>
                                <option value="2">กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม</option>
                                <option value="3">กลุ่มสาระการเรียนรู้คณิตศาสตร์</option>
                                <option value="4">กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี</option>
                                <option value="5">กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ</option>
                                <option value="6">กลุ่มสาระการงานอาชีพและเทคโนโลยี</option>
                                <option value="7">กลุ่มสาระศิลปะ ดนตรี นาฏศิลป์</option>
                                <option value="8">กลุ่มสาระสุขศึกษา พลศึกษา</option>
                                <option value="9">แนะแนวและห้องสมุด</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">เบอร์โทรศัพท์ (ห้ามซ้ำ)</label>
                            <input type="number" name="phone" id="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">G-mail (ห้ามซ้ำ)</label>
                            <input type="email" name="email" class="form-control" id="email">
                        </div>
                        <div class="mb-3">
                            <label for="telegram_id" class="form-label">ID Telegram (ห้ามซ้ำ)</label>
                            <input type="number" name="telegram_id" class="form-control" id="telegram_id">
                        </div>
                        <div class="mb-3">
                            <label for="position_id" class="form-label">บทบาท</label>
                            <select name="position_id" class="form-select" id="position_id" required>
                                <option value="1">ผู้บริหาร</option>
                                <option value="2">ครู</option>
                                <option value="3">บุคลากรทางการศึกษา</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแก้ไขผู้ใช้งาน -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">แก้ไขข้อมูลผู้ใช้งาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- แบบฟอร์มกรอกข้อมูลผู้ใช้งาน -->
                    <form action="edit_user.php" method="post">
                        <input type="hidden" name="personnel_id" id="edit_personnel_id">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name">
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name">
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้ (ห้ามซ้ำ)</label>
                            <input type="text" class="form-control" id="edit_username" name="username">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">สถานะ</label>
                            <select class="form-select" id="edit_role_id" name="role_id">
                                <option value="1">ผู้ใช้</option>
                                <option value="2">Admin</option>
                                <option value="3">ผู้อนุมัติ</option>
                                <option value="4">รอง</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subject_group_id" class="form-label">กลุ่มสาระการเรียนรู้</label>
                            <select name="subject_group_id" id="edit_subject_group_id" class="form-select">
                                <option value="1">กลุ่มสาระการเรียนรู้ภาษาไทย</option>
                                <option value="2">กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม</option>
                                <option value="3">กลุ่มสาระการเรียนรู้คณิตศาสตร์</option>
                                <option value="4">กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี</option>
                                <option value="5">กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ</option>
                                <option value="6">กลุ่มสาระการงานอาชีพและเทคโนโลยี</option>
                                <option value="7">กลุ่มสาระศิลปะ ดนตรี นาฏศิลป์</option>
                                <option value="8">กลุ่มสาระสุขศึกษา พลศึกษา</option>
                                <option value="9">แนะแนวและห้องสมุด</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">เบอร์โทรศัพท์ (ห้ามซ้ำ)</label>
                            <input type="number" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">G-mail (ห้ามซ้ำ)</label>
                            <input type="email" name="email" class="form-control" id="edit_email">
                        </div>
                        <div class="mb-3">
                            <label for="telegram_id" class="form-label">ID Telegram (ห้ามซ้ำ)</label>
                            <input type="number" name="telegram_id" class="form-control" id="edit_telegram_id">
                        </div>
                        <div class="mb-3">
                            <label for="position_id" class="form-label">บทบาท</label>
                            <select name="position_id" class="form-select" id="edit_position_id">
                                <option value="1">ผู้บริหาร</option>
                                <option value="2">ครู</option>
                                <option value="3">บุคลากรทางการศึกษา</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </form>
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
        $('#member-table').dataTable();
    });

    // เมื่อคลิกปุ่มแก้ไข ให้ดึงข้อมูลจาก data attributes ไปใส่ใน modal แก้ไข
    $('.editBtn').on('click', function(){
              var id = $(this).data('id');
              var firstName = $(this).data('first_name');
              var lastName = $(this).data('last_name');
              var username = $(this).data('username');
              var position = $(this).data('position');
              var subjectGroup = $(this).data('subject_group');
              var role = $(this).data('role');
              var telegramId = $(this).data('telegram');
              var email = $(this).data('email');  // ดึงข้อมูลอีเมล
              var phone = $(this).data('phone');

              $('#edit_personnel_id').val(id);
              $('#edit_first_name').val(firstName);
              $('#edit_last_name').val(lastName);
              $('#edit_username').val(username);
              $('#edit_position_id').val(position);
              $('#edit_subject_group_id').val(subjectGroup);
              $('#edit_role_id').val(role);
              $('#edit_telegram_id').val(telegramId);
              $('#edit_email').val(email); // แสดงค่าอีเมลในฟิลด์
              $('#edit_phone').val(phone);
          });


    </script>
    

</body>
</html>