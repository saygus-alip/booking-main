<?php
session_start();
include 'db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ดึงข้อมูลจากฟอร์ม
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $telegram_id = $_POST['telegram_id'];
    $role_id = $_POST['role_id'];
    $position_id = $_POST['position_id'];
    $subject_group_id = $_POST['subject_group_id'];

    // ตรวจสอบว่า email ลงท้ายด้วย @spa.ac.th เท่านั้น
    if (!preg_match('/@spa\.ac\.th$/', $email)) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Email ต้องลงท้ายด้วย @spa.ac.th เท่านั้น</div>";
        header("Location: members.php");
        exit;
    }

    // สร้างเงื่อนไข SQL แบบไดนามิกสำหรับตรวจสอบข้อมูลซ้ำ
    $sql_check = "SELECT * FROM personnel WHERE ";
    $conditions = [];
    $params = [];
    $types = "";

    if ($username !== '') {
        $conditions[] = "username = ?";
        $params[] = $username;
        $types .= "s";
    }
    if ($email !== '') {
        $conditions[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }
    if ($telegram_id !== '') {
        $conditions[] = "telegram_id = ?";
        $params[] = $telegram_id;
        $types .= "s";
    }
    if ($phone !== '') {
        $conditions[] = "phone = ?";
        $params[] = $phone;
        $types .= "s";
    }

    // ถ้าไม่มีฟิลด์ไหนที่มีข้อมูลเลย ให้ข้ามการตรวจสอบข้อมูลซ้ำ
    if (empty($conditions)) {
        $duplicate_found = false;
    } else {
        $sql_check .= implode(" OR ", $conditions);
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param($types, ...$params);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $duplicate_found = ($result->num_rows > 0);
    }

    if ($duplicate_found) {
        $_SESSION['message'] = "<div class='alert alert-danger'>ข้อมูลซ้ำ! โปรดตรวจสอบข้อมูลของคุณ</div>";
    } else {
        // ถ้าไม่พบข้อมูลซ้ำ ให้ทำการเพิ่มผู้ใช้งานใหม่
        $sql = "INSERT INTO personnel (first_name, last_name, username, password, phone, email, telegram_id, role_id, position_id, subject_group_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssii", $first_name, $last_name, $username, $password, $phone, $email, $telegram_id, $role_id, $position_id, $subject_group_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "<div class='alert alert-success'>เพิ่มผู้ใช้งานใหม่สำเร็จ!</div>";
        } else {
            $_SESSION['message'] = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
        }
    }

    // รีไดเร็กต์กลับไปที่หน้า members.php หลังจากทำงานเสร็จ
    header("Location: members.php");
    exit;
}
?>
