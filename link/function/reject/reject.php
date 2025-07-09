<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id']);
    $status_id = 3; // รหัสสถานะ "ไม่อนุมัติ"

    // ตรวจสอบว่า Booking_ID มีอยู่จริงในฐานข้อมูล
    $sql_check = "SELECT * FROM booking WHERE Booking_ID = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $booking_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    // อัปเดตสถานะการจอง
    $sql = "UPDATE booking SET Status_ID = ? WHERE Booking_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $status_id, $booking_id);

    if ($stmt->execute()) {
        echo "<script>alert('ไม่อนุมัติการจองสำเร็จ!'); window.location.href='reports.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง'); window.history.back();</script>";
    }
}

?>
