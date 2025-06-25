<?php
session_start();
include 'db_connect.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// ตรวจสอบว่าได้ส่งค่า id การจองมาหรือไม่
if (!isset($_GET['id'])) {
    header('Location: active_bookings.php');
    exit;
}

$bookingId = $_GET['id'];

// ดึงข้อมูลการจองมาเพื่อตรวจสอบเจ้าของ
$sql = "SELECT Personnel_ID FROM booking WHERE Booking_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "<div class='alert alert-danger'>ไม่พบข้อมูลการจอง</div>";
    header('Location: active_bookings.php');
    exit;
}

$row = $result->fetch_assoc();

// ตรวจสอบว่าเจ้าของการจองตรงกับผู้ใช้ที่ล็อกอินหรือไม่
if ($_SESSION['personnel_id'] != $row['Personnel_ID']) {
    $_SESSION['message'] = "<div class='alert alert-danger'>คุณไม่มีสิทธิ์ในการยกเลิกการจองนี้</div>";
    header('Location: active_bookings.php');
    exit;
}

// หากตรวจสอบผ่าน ให้ทำการลบข้อมูลการจอง
$sql = "DELETE FROM booking WHERE Booking_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);

if ($stmt->execute()) {
    $_SESSION['message'] = "<div class='alert alert-success'>ยกเลิกการจองเรียบร้อย</div>";
    header('Location: active_bookings.php');
    exit;
} else {
    $_SESSION['message'] = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการยกเลิกการจอง: " . $stmt->error . "</div>";
    header('Location: active_bookings.php');
    exit;
}
?>
