<?php 
session_start();
include 'db_connect.php'; // เชื่อมต่อกับฐานข้อมูล

// ตรวจสอบว่าอยู่ในหน้าที่ต้องการยกเว้นหรือไม่ เช่น main.php หรือ booking.php
$current_page = basename($_SERVER['PHP_SELF']);

$exempt_pages = ['main.php', 'booking.php'];

if (!in_array($current_page, $exempt_pages)) {
    // ตรวจสอบว่าผู้ใช้ได้ล็อกอินหรือไม่
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: index.php');
        exit;
    }


    //ezy
    

    // ตรวจสอบว่าไอดีผู้ใช้ยังอยู่ในฐานข้อมูลหรือไม่
    $personnel_id = $_SESSION['personnel_id'];
    $sql = "SELECT Personnel_ID FROM personnel WHERE Personnel_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $personnel_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        // ถ้าผู้ใช้ถูกลบออกจากฐานข้อมูล ให้ล้าง session และเปลี่ยนเส้นทางไปยังหน้า login
        session_unset(); // ลบ session ทั้งหมด
        session_destroy(); // ทำลาย session
        echo "<script>
                alert('บัญชีของคุณถูกลบออกจากระบบ');
                window.location.href='index.php';
              </script>";
        exit;
    }
}
?>
