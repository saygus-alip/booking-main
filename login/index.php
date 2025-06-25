<?php
session_start();
include 'db_connect.php'; // เชื่อมต่อกับฐานข้อมูล

// ตรวจสอบว่ามีการส่งแบบฟอร์มล็อกอินหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ตรวจสอบชื่อผู้ใช้ในฐานข้อมูล
    $sql = "SELECT * FROM personnel WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ตรวจสอบรหัสผ่านด้วย password_verify()
        if (password_verify($password, $user['Password'])) { // ตรวจสอบรหัสผ่านที่ถูกเข้ารหัส
            $_SESSION['loggedin'] = true;
            $_SESSION['personnel_id'] = $user['Personnel_ID'];
            $_SESSION['username'] = $username;
            $_SESSION['role_id'] = $user['Role_ID'];

            if (isset($_POST['remember'])) {
                // เก็บ cookie สำหรับจดจำผู้ใช้เป็นเวลา 10 ปี (3650 วัน)
                setcookie('personnel_id', $user['Personnel_ID'], time() + (86400 * 3650), "/");
                setcookie('username', $username, time() + (86400 * 3650), "/");
                setcookie('role_id', $user['Role_ID'], time() + (86400 * 3650), "/");
            }         

            // เปลี่ยนเส้นทางไปยังหน้าหลัก
            header('Location: main.php');
            exit;
        } else {
            $error_message = "รหัสผ่านไม่ถูกต้อง"; // แจ้งเตือนว่ารหัสผ่านไม่ถูกต้อง
        }
    } else {
        $error_message = "ชื่อผู้ใช้งานไม่ถูกต้อง"; // แจ้งเตือนว่าชื่อผู้ใช้ไม่ถูกต้อง
    }
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

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
        }
    }

    html,
    body {
        margin: 0;
        padding: 0;
        height: 100%;
        /* ให้ทั้งหน้าเว็บมีความสูงเต็มหน้าจอ */
        overflow-y: auto;
        /* อนุญาตการเลื่อนในแนวตั้ง */
    }

    body {
        display: flex;
        flex-direction: column;
    }

    main {
        flex-grow: 1;
        /* ให้ส่วนกลางของเนื้อหาขยายได้เต็มพื้นที่ */
    }

    .container-custom {
        background-color: #fff;
        padding: 0px;
        border-radius: 3px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        max-width: 360px;
        width: 100%;
    }

    .responsive-img {
        max-width: 100%;
        height: auto;
    }

    .navbar {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    .nav-link {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
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
    }

    .header-section {
        background-color: #e9ecef;
        color: black;
        padding: 20px;
        border-radius: 3px 3px 0 0;
        height: 70px;
        display: flex;
        align-items: center;
        border: 1px solid #e0e0e0;
    }

    .form-section {
        padding: 20px;
        background-color: white;
        border-radius: 0 0 3px 3px;
        border: 1px solid #e0e0e0;
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

    .alert-custom {
        width: 100%;
        max-width: 360px;
        margin-bottom: 15px;
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
                        <a class="nav-link dropdown-toggle <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['my_bookings.php', 'upcoming_bookings.php', 'past_bookings.php']) ? 'active' : ''); ?>"
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
                        <a href="booking.php" class="nav-link">จองห้อง</a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">เข้าสู่ระบบ</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>




    <div class="full-height">

        <div class="container container-custom">
            <div class="header-section">
                <div style="font-size: 20px">เข้าสู่ระบบ - Login</div>
            </div>
            <div class="form-section">
                <!-- แสดงข้อผิดพลาดถ้ามี -->
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้งาน</label>
                        <input type="text" class="form-control" id="username" name="username"
                            placeholder="ชื่อผู้ใช้ของคุณ" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="รหัสผ่านของคุณ" required>
                    </div>
                    <div>
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label for="remember" class="form-check-label">จำการเข้าระบบ</label>
                    </div>
                    <button type="submit" class="btn btn-outline-custom mt-3">
                        <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>