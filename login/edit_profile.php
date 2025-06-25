<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

include 'db_connect.php'; // เชื่อมต่อกับฐานข้อมูล
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

// ดึงข้อมูลตำแหน่ง
$positions = [];
$position_query = "SELECT Position_ID, Position_Name FROM position";
$result = $conn->query($position_query);
while ($row = $result->fetch_assoc()) {
    $positions[] = $row;
}

// ดึงข้อมูลสถานะ
$roles = [];
$role_query = "SELECT Role_ID, Role_Name FROM role";
$result = $conn->query($role_query);
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
}

// ดึงข้อมูลกลุ่มสาระการเรียนรู้
$subject_groups = [];
$subject_group_query = "SELECT Subject_Group_ID, Subject_Group_Name FROM subject_group";
$result = $conn->query($subject_group_query);
while ($row = $result->fetch_assoc()) {
    $subject_groups[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม (แม้จะเป็นค่าว่างก็รับมา)
    $posted_first_name   = isset($_POST['first_name']) ? $_POST['first_name'] : '';
    $posted_last_name    = isset($_POST['last_name']) ? $_POST['last_name'] : '';
    $posted_email        = isset($_POST['email']) ? $_POST['email'] : '';
    $posted_phone        = isset($_POST['phone']) ? $_POST['phone'] : '';
    $posted_telegram_id  = isset($_POST['telegram_id']) ? $_POST['telegram_id'] : '';
    $posted_new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $posted_confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // ตรวจสอบกรณีรหัสผ่าน ถ้ามีการกรอก (แม้จะเป็นค่าว่างก็ถือว่าไม่ต้องอัปเดต)
    if ($posted_new_password !== '') {
        if ($posted_new_password !== $posted_confirm_password) {
            echo "<script>
                    alert('รหัสผ่านไม่ตรงกัน!');
                    window.location.href='edit_profile.php';
                  </script>";
            exit;
        }
    }

    // ตรวจสอบข้อมูลซ้ำแบบแยกฟิลด์
    // สำหรับ Email หากมีการเปลี่ยนแปลงและไม่เป็นค่าว่าง
    if ($posted_email !== $email && $posted_email !== '') {
        $stmt = $conn->prepare("SELECT Personnel_ID FROM personnel WHERE Email = ? AND Personnel_ID != ?");
        $stmt->bind_param('si', $posted_email, $personnel_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>
                    alert('อีเมลนี้มีผู้ใช้งานแล้ว!');
                    window.location.href='edit_profile.php';
                  </script>";
            exit;
        }
        $stmt->close();
    }

    // สำหรับ Telegram ID หากมีการเปลี่ยนแปลงและไม่เป็นค่าว่าง
    if ($posted_telegram_id !== $telegram_id && $posted_telegram_id !== '') {
        $stmt = $conn->prepare("SELECT Personnel_ID FROM personnel WHERE Telegram_ID = ? AND Personnel_ID != ?");
        $stmt->bind_param('si', $posted_telegram_id, $personnel_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>
                    alert('Telegram ID นี้มีผู้ใช้งานแล้ว!');
                    window.location.href='edit_profile.php';
                  </script>";
            exit;
        }
        $stmt->close();
    }

    // สร้างอาร์เรย์สำหรับเก็บ field ที่มีการเปลี่ยนแปลง (แม้จะเป็นค่าว่าง) และค่าที่จะ bind
    $updateFields = [];
    $params = [];
    $paramTypes = "";

    if ($posted_first_name !== $first_name) {
        $updateFields[] = "First_Name = ?";
        $params[] = $posted_first_name;
        $paramTypes .= "s";
    }

    if ($posted_last_name !== $last_name) {
        $updateFields[] = "Last_Name = ?";
        $params[] = $posted_last_name;
        $paramTypes .= "s";
    }

    if ($posted_email !== $email) {
        $updateFields[] = "Email = ?";
        $params[] = $posted_email;
        $paramTypes .= "s";
    }

    if ($posted_phone !== $phone) {
        $updateFields[] = "Phone = ?";
        $params[] = $posted_phone;
        $paramTypes .= "s";
    }

    if ($posted_telegram_id !== $telegram_id) {
        $updateFields[] = "Telegram_ID = ?";
        $params[] = $posted_telegram_id;
        $paramTypes .= "s";
    }

    if ($posted_new_password !== '') {
        $updateFields[] = "Password = ?";
        $hashed_password = password_hash($posted_new_password, PASSWORD_DEFAULT);
        $params[] = $hashed_password;
        $paramTypes .= "s";
    }

    // หากไม่มีการเปลี่ยนแปลงใด ๆ
    if (empty($updateFields)) {
        echo "<script>
                alert('ไม่มีการเปลี่ยนแปลงข้อมูล!');
                window.location.href='edit_profile.php';
              </script>";
        exit;
    }

    // สร้างคำสั่ง SQL แบบไดนามิกสำหรับอัปเดตข้อมูล
    $sql = "UPDATE personnel SET " . implode(", ", $updateFields) . " WHERE Personnel_ID = ?";
    $params[] = $personnel_id;
    $paramTypes .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($paramTypes, ...$params);

    if ($stmt->execute()) {
        // อัปเดต session สำหรับข้อมูลที่เปลี่ยนแปลง
        if ($posted_first_name !== $first_name) {
            $_SESSION['first_name'] = $posted_first_name;
        }
        if ($posted_last_name !== $last_name) {
            $_SESSION['last_name'] = $posted_last_name;
        }
        if ($posted_phone !== $phone) {
            $_SESSION['phone'] = $posted_phone;
        }
        if ($posted_telegram_id !== $telegram_id) {
            $_SESSION['telegram_id'] = $posted_telegram_id;
        }

        echo "<script>
                alert('ข้อมูลอัปเดตเรียบร้อยแล้ว!');
                window.location.href='edit_profile.php';
              </script>";
        exit;
    } else {
        echo "<script>
                alert('เกิดข้อผิดพลาด: " . addslashes($stmt->error) . "');
                window.location.href='edit_profile.php';
              </script>";
        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลผู้ใช้</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
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
        max-width: 1000px;
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
        max-width: 1000px;
        width: 100%;

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
            <div style="font-size: 20px">แก้ไขข้อมูลผู้ใช้งาน</div>
        </div>
        <div class="container container-custom">
            <form action="edit_profile.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">อีเมล</label>
                    <input type="email" id="email" class="form-control" name="email" value="<?php echo $email; ?>"
                        readonly>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                    <input type="password" id="new_password" class="form-control" name="new_password"
                        placeholder="รหัสผ่านต้องไม่น้อยกว่า 4 ตัวอักษร">
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                    <input type="password" id="confirm_password" class="form-control" name="confirm_password"
                        placeholder="กรอกรหัสผ่านอีกครั้ง">
                </div>
                <div class="mb-3">
                    <label for="first_name" class="form-label">ชื่อจริง</label>
                    <input type="text" id="first_name" class="form-control" name="first_name"
                        placeholder="แก้ไขชื่อของคุณ" value="<?php echo $first_name; ?>">
                </div>
                <div class="mb-3">
                    <label for="last_name" class="form-label">นามสกุล</label>
                    <input type="text" id="last_name" class="form-control" name="last_name"
                        placeholder="แก้ไขนามสกุลของคุณ" value="<?php echo $last_name; ?>">
                </div>
                <div class="mb-3">
                    <label for="position_id" class="form-label">ตำแหน่ง</label>
                    <select class="selectpicker form-select" data-live-search="true" id="position_id" name="position_id"
                        disabled>
                        <?php foreach ($positions as $position): ?>
                        <option value="<?php echo $position['Position_ID']; ?>"
                            <?php if ($position['Position_ID'] == $position_id) echo 'selected'; ?>>
                            <?php echo $position['Position_Name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="subject_group_id" class="form-label">กลุ่มสาระการเรียนรู้</label>
                    <select class="selectpicker form-select" data-live-search="true" id="subject_group_id"
                        name="subject_group_id" disabled>
                        <?php foreach ($subject_groups as $group): ?>
                        <option value="<?php echo $group['Subject_Group_ID']; ?>"
                            <?php if ($group['Subject_Group_ID'] == $subject_group_id) echo 'selected'; ?>>
                            <?php echo $group['Subject_Group_Name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="role_id" class="form-label">สถานะสมาชิก</label>
                    <select class="selectpicker form-select" data-live-search="true" id="role_id" name="role_id"
                        disabled>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['Role_ID']; ?>"
                            <?php if ($role['Role_ID'] == $role_id) echo 'selected'; ?>>
                            <?php echo $role['Role_Name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="telegram_id" class="form-label">Telegram ID</label>
                    <input type="number" id="telegram_id" class="form-control" name="telegram_id" value="<?php echo $telegram_id; ?>"
                        >
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                    <input type="number" id="phone" class="form-control" name="phone" value="<?php echo $phone; ?>"
                        >
                </div>

                <button class="btn btn-outline-dark">บันทึกข้อมูลผู้ใช้ใหม่</button>
            </form>
        </div>
    </div>



    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</body>

</html>