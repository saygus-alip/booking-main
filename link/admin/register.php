<?php
require_once '../database/db_connect.php';

// กำหนด IP Address ของเจ้าของเซิร์ฟเวอร์
$allowed_ip = '::1'; // เปลี่ยนเป็น IP ของคุณที่อนุญาตให้เข้าถึง

// ตรวจสอบ IP Address ของผู้ที่เข้าถึง
if ($_SERVER['REMOTE_ADDR'] !== $allowed_ip) {
    die("<div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>");

}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $position_id = $_POST['position_id'];
    $subject_group_id = $_POST['subject_group_id'];
    $role_id = $_POST['role_id'];

    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // บันทึกข้อมูลผู้ใช้ใหม่
    $sql = "INSERT INTO personnel (Username, Password, First_Name, Last_Name, Position_ID, Subject_Group_ID, Role_ID) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiii", $username, $hashed_password, $first_name, $last_name, $position_id, $subject_group_id, $role_id);

    if ($stmt->execute()) {
        $error_message = "ลงทะเบียนสำเร็จ!";
    } else {
        $error_message = "เกิดข้อผิดพลาด: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Admin</title>
    <link rel="stylesheet" href="../boostarp/css/bootstrap.min.css">
    <link rel="stylesheet" href="../font/css/all.min.css">
    <link rel="stylesheet" href="../css/index.css">
</head>

<body>
    <div class="full-height">
        <div class="container container-custom">
            <div class="header-section">
                <div style="font-size: 20px">Register Admin</div>
            </div>
            <div class="form-section">
                <!-- แสดงข้อผิดพลาดถ้ามี -->
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form action="register" method="POST">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">ชื่อ</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                            placeholder="ชื่อของคุณ" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">นามสกุล</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                            placeholder="นามสกุลของคุณ" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้งาน</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="text" class="form-control" id="password" name="password"
                            placeholder="รหัสผ่านของคุณ" required>
                    </div>
                    <div class="mb-3">
                        <label for="position_id" class="form-label">ตำแหน่ง</label>
                        <select class="form-select" id="position_id" name="position_id" required>
                            <option value="">เลือกตำแหน่ง</option>
                            <option value="1">ผู้บริหาร</option>
                            <option value="2">ครู</option>
                            <option value="3">บุคลากรทางการศึกษา</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subject_group_id" class="form-label">กลุ่มสาระการเรียนรู้</label>
                        <select class="form-select" name="subject_group_id" id="subject_group_id" required>
                            <option value="">เลือกกลุ่มสาระการเรียนรู้</option>
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
                        <label for="role_id" class="form-label">บทบาท</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <option value="">เลือกบทบาท</option>
                            <option value="1">ผู้ใช้</option>
                            <option value="2">Admin</option>
                            <option value="3">ผู้อำนวยการ</option>
                            <option value="4">รองผู้อำนวยการ</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-custom mt-3">
                        <i class="fas fa-sign-in-alt"></i> สร้างรหัส
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>