<?php
include 'db_connect.php'; // ตรวจสอบการเชื่อมต่อฐานข้อมูล

if (isset($_GET['id'])) {
    $personnel_id = $_GET['id'];

    // ตรวจสอบว่า personnel_id เป็นตัวเลขหรือไม่
    if (is_numeric($personnel_id)) {
        $sql = "SELECT * FROM personnel WHERE personnel_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $personnel_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            echo json_encode($userData); // ส่งข้อมูลในรูปแบบ JSON
        } else {
            // ส่งข้อความแจ้งว่าไม่พบข้อมูล
            echo json_encode(['message' => 'ไม่พบข้อมูลผู้ใช้']);
        }
    } else {
        echo json_encode(['message' => 'ID ผู้ใช้ไม่ถูกต้อง']); // ถ้า ID ไม่เป็นตัวเลข
    }
} else {
    echo json_encode(['message' => 'ไม่พบข้อมูลผู้ใช้']); // ถ้าไม่ได้ส่ง ID มา
}

$conn->close();
?>
