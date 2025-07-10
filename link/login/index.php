<?php
session_start();
require_once '../database/db_connect.php'; 

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
    <link rel="stylesheet" href="../boostarp/css/bootstrap.min.css">
    <link rel="stylesheet" href="../font/css/all.min.css">
    <link rel="stylesheet" href="../css/index.css">
</head>

<body>

    <?php require_once '../navbar/navbar_index.php'; ?>

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

    <script src="../boostarp/js/bootstrap.bundle.min.js"></script>
</body>

</html>