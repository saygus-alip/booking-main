<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

include 'db_connect.php';
include 'auth_check.php';

// หากมีการส่งข้อมูลสำหรับแก้ไข (POST) และมี personnel_id ส่งมา
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['personnel_id'])) {
    $update_personnel_id = $_POST['personnel_id'];

    // ดึงข้อมูลที่ส่งเข้ามาจากฟอร์ม (ให้แน่ใจว่าได้ลบ attribute "required" ในฟอร์มแก้ไขแล้ว เพื่อให้กรอกเฉพาะบาง field ก็ได้)
    $first_name       = $_POST['first_name']       ?? null;
    $last_name        = $_POST['last_name']        ?? null;
    $username         = $_POST['username']         ?? null;
    $phone            = $_POST['phone']            ?? null;
    $email            = $_POST['email']            ?? null;
    $telegram_id      = $_POST['telegram_id']      ?? null;
    $role_id          = $_POST['role_id']          ?? null;
    $position_id      = $_POST['position_id']      ?? null;
    $subject_group_id = $_POST['subject_group_id'] ?? null;

    // ตรวจสอบว่า email ถ้ามีการกรอกข้อมูล ต้องลงท้ายด้วย @spa.ac.th เท่านั้น
    if (!empty($email) && !preg_match('/@spa\.ac\.th$/', $email)) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Email ต้องลงท้ายด้วย @spa.ac.th เท่านั้น</div>";
        header('Location: members.php');
        exit;
    }

    // ตรวจสอบข้อมูลซ้ำเฉพาะกรณีที่มีการกรอกค่า (ไม่ใช่ค่าว่าง)
    $dupConditions = [];
    $dupParams = [];
    $dupTypes = "";
    if ($username !== "") {
        $dupConditions[] = "username = ?";
        $dupParams[] = $username;
        $dupTypes .= "s";
    }
    if ($email !== "") {
        $dupConditions[] = "email = ?";
        $dupParams[] = $email;
        $dupTypes .= "s";
    }
    if ($telegram_id !== "") {
        $dupConditions[] = "telegram_id = ?";
        $dupParams[] = $telegram_id;
        $dupTypes .= "s";
    }
    if ($phone !== "") {
        $dupConditions[] = "phone = ?";
        $dupParams[] = $phone;
        $dupTypes .= "s";
    }
    if (!empty($dupConditions)) {
        $sql_check = "SELECT * FROM personnel WHERE (" . implode(" OR ", $dupConditions) . ") AND personnel_id != ?";
        $dupParams[] = $update_personnel_id;
        $dupTypes .= "i";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param($dupTypes, ...$dupParams);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $_SESSION['message'] = "<div class='alert alert-danger'>ข้อมูลซ้ำ! โปรดตรวจสอบข้อมูลของคุณ</div>";
            header('Location: members.php');
            exit;
        }
        $stmt_check->close();
    }
    
    // เตรียม dynamic update โดยเพิ่มเฉพาะฟิลด์ที่มีการส่งค่าเข้ามา (แม้จะเป็นค่าว่างก็อัปเดต)
    $fields = [];
    $params = [];
    $types = "";
    
    if (isset($_POST['first_name'])) {
        $fields[] = "first_name = ?";
        $params[] = $first_name;
        $types .= "s";
    }
    if (isset($_POST['last_name'])) {
        $fields[] = "last_name = ?";
        $params[] = $last_name;
        $types .= "s";
    }
    if (isset($_POST['username'])) {
        $fields[] = "username = ?";
        $params[] = $username;
        $types .= "s";
    }
    if (isset($_POST['phone'])) {
        $fields[] = "phone = ?";
        $params[] = $phone;
        $types .= "s";
    }
    if (isset($_POST['email'])) {
        $fields[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }
    if (isset($_POST['telegram_id'])) {
        $fields[] = "telegram_id = ?";
        $params[] = $telegram_id;
        $types .= "s";
    }
    if (isset($_POST['role_id'])) {
        $fields[] = "role_id = ?";
        $params[] = $role_id;
        $types .= "i";
    }
    if (isset($_POST['position_id'])) {
        $fields[] = "position_id = ?";
        $params[] = $position_id;
        $types .= "i";
    }
    if (isset($_POST['subject_group_id'])) {
        $fields[] = "subject_group_id = ?";
        $params[] = $subject_group_id;
        $types .= "i";
    }
    
    // สำหรับรหัสผ่าน: หากมีการกรอกรหัสผ่านใหม่ ให้ทำการ hash แล้วเพิ่มลงในรายการ update
    // ไม่ต้องตรวจสอบรหัสผ่านเก่า
    if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
        $new_hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $fields[] = "password = ?";
        $params[] = $new_hashed_password;
        $types .= "s";
    }
    
    // ตรวจสอบว่ามีฟิลด์ที่จะอัปเดตหรือไม่
    if (empty($fields)) {
        $_SESSION['message'] = "<div class='alert alert-warning'>ไม่พบข้อมูลที่ต้องการอัปเดต</div>";
        header('Location: members.php');
        exit;
    }
    
    // สร้าง query แบบ dynamic
    $sql_update = "UPDATE personnel SET " . implode(", ", $fields) . " WHERE personnel_id = ?";
    $params[] = $update_personnel_id;
    $types .= "i";
    
    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        header('Location: members.php');
        exit;
    }
    
    $stmt_update->bind_param($types, ...$params);
    
    if ($stmt_update->execute()) {
        $_SESSION['message'] = "<div class='alert alert-success'>ข้อมูลถูกอัปเดตสำเร็จ!</div>";
    } else {
        $_SESSION['message'] = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $stmt_update->error . "</div>";
    }
    
    $stmt_update->close();
    header('Location: members.php');
    exit;
}
?>
