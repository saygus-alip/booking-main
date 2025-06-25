<?php
session_start();
include 'db_connect.php'; // เชื่อมต่อกับฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $hall_name   = $_POST['hall_name'];
    $hall_detail = $_POST['hall_detail'];
    $hall_size   = $_POST['hall_size'];
    $capacity    = $_POST['capacity'];
    $status_hall = $_POST['status_hall']; // สถานะห้อง

    $hall_image  = '';
    $dot_color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));

    // ตรวจสอบห้องที่มีชื่อซ้ำ
    $sql_check = "SELECT * FROM hall WHERE Hall_Name = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $hall_name);
    $stmt_check->execute(); 
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION['message'] = "<div class='alert alert-danger'>ห้องนี้มีอยู่แล้วในระบบ!</div>";
    } else {
        // ตรวจสอบและจัดการไฟล์รูปภาพ (ถ้ามี)
        if (isset($_FILES['hall_image']) && $_FILES['hall_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            $fileName  = basename($_FILES['hall_image']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileExtension, $allowedTypes)) {
                $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
                $targetFilePath = $uploadDir . $newFileName;
                if (move_uploaded_file($_FILES['hall_image']['tmp_name'], $targetFilePath)) {
                    $hall_image = $targetFilePath;
                } else {
                    $_SESSION['message'] = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ</div>";
                    header("Location: booking.php");
                    exit;
                }
            } else {
                $_SESSION['message'] = "<div class='alert alert-danger'>ประเภทรูปภาพไม่ถูกต้อง (อนุญาตเฉพาะ JPG, JPEG, PNG, GIF)</div>";
                header("Location: booking.php");
                exit;
            }
        }
        
        // เพิ่มห้องใหม่พร้อมรูปภาพ
        $sql = "INSERT INTO hall (Hall_Name, Hall_Detail, Hall_Size, Capacity, Status_Hall, Hall_Image, Dot_Color) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // ตัวอย่างนี้ใช้ bind_param("ssssi s") แต่ต้องไม่มีช่องว่างในสตริงรูปแบบ
        // ลำดับ: Hall_Name (s), Hall_Detail (s), Hall_Size (s), Capacity (i), Status_Hall (i), Hall_Image (s)
        $stmt->bind_param("sssiiss", $hall_name, $hall_detail, $hall_size, $capacity, $status_hall, $hall_image, $dot_color);

        if ($stmt->execute()) {
            $_SESSION['message'] = "<div class='alert alert-success'>ห้องถูกเพิ่มสำเร็จ!</div>";
        } else {
            $_SESSION['message'] = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }

    // รีไดเร็กต์กลับไปที่ booking.php หลังจากดำเนินการเสร็จ
    header("Location: booking.php");
    exit;
}
?>
