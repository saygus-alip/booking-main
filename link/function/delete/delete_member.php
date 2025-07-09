<?php
// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
include 'db_connect.php';

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (isset($_GET['id'])) {
    $personnel_id = $_GET['id'];

    // คำสั่ง SQL สำหรับการลบข้อมูล
    $sql = "DELETE FROM personnel WHERE personnel_id = ?";
    
    // เตรียมคำสั่ง SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $personnel_id);  // ใช้ "i" เพราะเป็นตัวแปรประเภท integer

    // ตรวจสอบว่าการลบสำเร็จหรือไม่
    if ($stmt->execute()) {
        $_SESSION['message'] = "<div class='alert alert-success'>ลบข้อมูลสมาชิกเรียบร้อยแล้ว</div>";
    } else {
        $_SESSION['message'] = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
    }

    // ปิดการเชื่อมต่อฐานข้อมูล
    $stmt->close();
    $conn->close();

    // รีไดเรคไปหน้ารายการสมาชิก (members.php)
    header('Location: members.php');
    exit;
} else {
    // ถ้าไม่ได้ส่ง ID มาจะบอกว่าไม่พบข้อมูล
    $_SESSION['message'] = "<div class='alert alert-danger'>ไม่พบข้อมูลที่ต้องการลบ</div>";
    header('Location: members.php');
    exit;
}
?>
