<?php
header("Content-Type: application/json");
include 'db_connect.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die(json_encode(["error" => "เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $conn->connect_error]));
}

// ตัวอย่าง: ดึงเฉพาะรายการที่ Status_ID = 4 (อนุมัติแล้ว) 
// (ปรับตามเงื่อนไขจริงในระบบของคุณ)
$sql = "
    SELECT 
    b.Booking_ID,
    b.Date_Start,
    b.Time_Start,
    b.Time_End,
    b.Attendee_Count,
    b.Booking_Detail,
    b.Topic_Name,
    b.Status_ID,
    h.Dot_Color AS DotColor,  -- ใช้ h. แทน b.
    h.Hall_Name,
    p.First_Name,
    p.Last_Name,
    p.Phone
FROM booking b
LEFT JOIN hall h ON b.Hall_ID = h.Hall_ID
LEFT JOIN personnel p ON b.Personnel_ID = p.Personnel_ID
WHERE b.Status_ID = 4

";

$result = $conn->query($sql);
$color = $row['DotColor'];


$bookings = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        
        // รวมชื่อเป็นสตริงเดียว เช่น "สมชาย ใจดี"
        $bookerName = $row['First_Name'] . ' ' . $row['Last_Name'];
        
        // สร้างฟิลด์ booking_time เป็นช่วงเวลา "HH:MM - HH:MM"
        $bookingTime = $row["Time_Start"] . " - " . $row["Time_End"];
        
        // กำหนดรายละเอียดเพิ่มเติม
        // เช่น "Topic_Name - Booking_Detail (Attendee_Count คน)"
        $detailsText = $row['Booking_Detail'];

        // ดึงสีจากคอลัมน์ Dot-Color (ใช้ bracket notation เพราะชื่อคอลัมน์มีเครื่องหมาย -)
        $color = $row['DotColor'];

        $bookings[] = [
            // วันที่ (YYYY-MM-DD) สำหรับใช้เทียบในปฏิทิน
            "date"         => $row["Date_Start"],
            // ข้อมูลห้อง
            "room_name"    => $row["Hall_Name"],
            // ชื่อผู้จอง
            "booker_name"  => $bookerName,
            // สมมุติว่าเอา Email มาแทน "เบอร์โทร"
            "booker_phone" => $row["Phone"],
            // ช่วงเวลา
            "booking_time" => $bookingTime,
            // รายละเอียด
            "details"      => $detailsText,
            // สีจุดในปฏิทิน
            "color"        => $color
        ];
    }
}

$conn->close();

// ส่งข้อมูลออกไปในรูปแบบ JSON
echo json_encode($bookings);
?>