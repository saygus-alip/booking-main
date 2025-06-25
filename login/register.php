<?php
include 'db_connect.php';

// กำหนด IP Address ของเจ้าของเซิร์ฟเวอร์
$allowed_ip = '::1'; // เปลี่ยนเป็น IP ของคุณที่อนุญาตให้เข้าถึง

// ตรวจสอบ IP Address ของผู้ที่เข้าถึง
if ($_SERVER['REMOTE_ADDR'] !== $allowed_ip) {
    die("<div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>");

}


$secret_code = "12345"; // รหัสลับที่ต้องกรอกก่อนลงทะเบียน

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $position_id = $_POST['position_id'];
    $subject_group_id = $_POST['subject_group_id'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];
    $telegram_id = $_POST['telegram_id'];
    $entered_secret_code = $_POST['secret_code'];

    // ตรวจสอบรหัสลับก่อน
    if ($entered_secret_code !== $secret_code) {
        echo "<div class='alert alert-danger'>รหัสลับไม่ถูกต้อง</div>";
    } else {
        // เข้ารหัสรหัสผ่าน
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // บันทึกข้อมูลผู้ใช้ใหม่
        $sql = "INSERT INTO personnel (Username, Password, First_Name, Last_Name, Position_ID, Subject_Group_ID, Phone, Email, Role_ID, Telegram_ID) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiissis", $username, $hashed_password, $first_name, $last_name, $position_id, $subject_group_id, $phone, $email, $role_id, $telegram_id);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>ลงทะเบียนสำเร็จ!</div>";
        } else {
            echo "เกิดข้อผิดพลาด: " . $stmt->error;
        }
    }
}
?>

<!-- ฟอร์มลงทะเบียน -->
<form action="register.php" method="POST">
    <label for="first_name">ชื่อ:</label>
    <input type="text" name="first_name" required><br>
    
    <label for="last_name">นามสกุล:</label>
    <input type="text" name="last_name" required><br>
    
    <label for="username">ชื่อผู้ใช้:</label>
    <input type="text" name="username" required><br>
    
    <label for="password">รหัสผ่าน:</label>
    <input type="password" name="password" required><br>
    
    <label for="position_id">ตำแหน่ง:</label>
    <select name="position_id" required>
        <option value="1">ผู้บริหาร</option>
        <option value="2">ครู</option>
        <option value="3">บุคลากรทางการศึกษา</option>
    </select><br>
    
    <label for="subject_group_id">กลุ่มสาระการเรียนรู้:</label>
        <select name="subject_group_id" required>
            <option value="1">กลุ่มสาระการเรียนรู้ภาษาไทย</option>
            <option value="2">กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม</option>
            <option value="3">กลุ่มสาระการเรียนรู้คณิตศาสตร์</option>
            <option value="4">กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี</option>
            <option value="5">กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ</option>
            <option value="6">กลุ่มสาระการงานอาชีพและเทคโนโลยี</option>
            <option value="7">กลุ่มสาระศิลปะ ดนตรี นาฏศิลป์</option>
            <option value="8">กลุ่มสาระสุขศึกษา พลศึกษา</option>
            <option value="9">แนะแนวและห้องสมุด</option>
        </select><br>

    
    <label for="phone">เบอร์โทรศัพท์:</label>
    <input type="text" name="phone" required><br>
    
    <label for="email">อีเมล:</label>
    <input type="email" name="email" required><br>

    <label for="telegram_id">ไอดีเทเลแกรม:</label>
    <input type="text" name="telegram_id" required><br>
    
    <label for="role_id">บทบาท:</label>
    <select name="role_id" required>
        <option value="1">ผู้ใช้</option>
        <option value="2">Admin</option>
        <option value="3">ผู้อำนวยการ</option>
        <option value="4">รองผู้อำนวยการ</option>
    </select><br>

    <label for="secret_code">รหัสลับ:</label>
    <input type="text" name="secret_code" placeholder="กรอกรหัสลับ" required><br>

    <button type="submit">ลงทะเบียน</button>
</form>
