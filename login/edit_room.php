<?php
session_start();
include 'db_connect.php'; // เชื่อมต่อกับฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hall_id'])) {
    // รับข้อมูลจากฟอร์ม
    $hall_id     = $_POST['hall_id'];
    $hall_name   = $_POST['hall_name'];
    $hall_detail = $_POST['hall_detail'];
    $hall_size   = $_POST['hall_size'];
    $capacity    = $_POST['capacity'];
    $status_hall = $_POST['status_hall'];

    // ดึงข้อมูลเดิมจากฐานข้อมูลเพื่อตรวจสอบการเปลี่ยนแปลง
    $sql_check = "SELECT Hall_Name, Hall_Detail, Hall_Size, Capacity, Status_Hall, Hall_Image FROM hall WHERE Hall_ID = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $hall_id);
    $stmt_check->execute();
    $stmt_check->bind_result($old_hall_name, $old_hall_detail, $old_hall_size, $old_capacity, $old_status_hall, $old_hall_image);
    $stmt_check->fetch();
    $stmt_check->close();

    // ตรวจสอบว่าข้อมูลที่กรอกใหม่มีการเปลี่ยนแปลงหรือไม่
    // ในกรณีที่ไม่มีการเลือกอัปโหลดรูปใหม่ เราจะเทียบกับข้อมูลเดิม
    if (
        $hall_name   == $old_hall_name &&
        $hall_detail == $old_hall_detail &&
        $hall_size   == $old_hall_size &&
        $capacity    == $old_capacity &&
        $status_hall == $old_status_hall &&
        (!isset($_FILES['hall_image']['name']) || empty($_FILES['hall_image']['name']))
    ) {
        $_SESSION['message'] = "<div class='alert alert-info'>ไม่มีการเปลี่ยนแปลงข้อมูล</div>";
        header('Location: booking.php');
        exit;
    }

    // ตรวจสอบว่ามีห้องที่ชื่อเดียวกันอยู่แล้วหรือไม่ (เฉพาะกรณีที่เปลี่ยนชื่อห้อง)
    if ($hall_name != $old_hall_name) {
        $sql_check_name = "SELECT * FROM hall WHERE Hall_Name = ? AND Hall_ID != ?";
        $stmt_check_name = $conn->prepare($sql_check_name);
        $stmt_check_name->bind_param("si", $hall_name, $hall_id);
        $stmt_check_name->execute();
        $result_check_name = $stmt_check_name->get_result();

        if ($result_check_name->num_rows > 0) {
            $_SESSION['message'] = "<div class='alert alert-danger'>ห้องนี้มีชื่อซ้ำแล้วในระบบ!</div>";
            header('Location: booking.php');
            exit;
        }
    }

    // ตัวแปรสำหรับเก็บ path ของรูปใหม่ (ถ้ามี)
    $uploadPath = $old_hall_image; // ค่าเริ่มต้นคือข้อมูลเดิม
    $update_image = false;

    // ตรวจสอบว่ามีการอัปโหลดรูปใหม่หรือไม่
    if (isset($_FILES['hall_image']) && $_FILES['hall_image']['error'] == 0 && !empty($_FILES['hall_image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $fileName = $_FILES['hall_image']['name'];
        $fileTmp  = $_FILES['hall_image']['tmp_name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // ตรวจสอบนามสกุลของไฟล์
        if (!in_array($fileExt, $allowed)) {
            $_SESSION['message'] = "<div class='alert alert-danger'>รูปภาพที่อัปโหลดไม่ถูกต้อง! อนุญาตเฉพาะไฟล์: " . implode(", ", $allowed) . "</div>";
            header('Location: booking.php');
            exit;
        }

        // กำหนดโฟลเดอร์ที่จะเก็บไฟล์อัปโหลด
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        // สร้างชื่อไฟล์ใหม่เพื่อป้องกันการซ้ำกัน
        $newFileName = uniqid() . "." . $fileExt;
        $uploadPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($fileTmp, $uploadPath)) {
            $_SESSION['message'] = "<div class='alert alert-danger'>อัปโหลดรูปภาพไม่สำเร็จ!</div>";
            header('Location: booking.php');
            exit;
        }
        $update_image = true;

        // ตัวเลือก: หากต้องการลบรูปเก่าออกจากเซิร์ฟเวอร์ สามารถเปิดใช้งานส่วนนี้ได้
        /*
        if (file_exists($old_hall_image)) {
            unlink($old_hall_image);
        }
        */
    }

    // เตรียมคำสั่งอัปเดตข้อมูลห้องประชุม
    if ($update_image) {
        // หากมีการอัปโหลดรูปใหม่ ให้แก้ไขคอลัมน์ Hall_Image ด้วย
        $sql_update = "UPDATE hall SET Hall_Name = ?, Hall_Detail = ?, Hall_Size = ?, Capacity = ?, Status_Hall = ?, Hall_Image = ? WHERE Hall_ID = ?";
        // เปลี่ยน type specifier ของ $status_hall เป็น i (integer) แทน s
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssiisi", $hall_name, $hall_detail, $hall_size, $capacity, $status_hall, $uploadPath, $hall_id);
    } else {
        // หากไม่มีการอัปโหลดรูปใหม่ ให้คงค่า Hall_Image เดิมไว้
        $sql_update = "UPDATE hall SET Hall_Name = ?, Hall_Detail = ?, Hall_Size = ?, Capacity = ?, Status_Hall = ? WHERE Hall_ID = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssiii", $hall_name, $hall_detail, $hall_size, $capacity, $status_hall, $hall_id);
    }

    if ($stmt_update->execute()) {
        $_SESSION['message'] = "<div class='alert alert-success'>ห้องถูกอัปเดตสำเร็จ!</div>";
    } else {
        $_SESSION['message'] = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $stmt_update->error . "</div>";
    }

    header('Location: booking.php');
    exit;
} else {
    header('Location: booking.php');
    exit;
}
?>
