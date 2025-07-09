<?php
session_start();
require_once '../database/db_connect.php'; 

// ดึงข้อมูลจำนวนห้องในแต่ละสถานะ
$sql_pending = "SELECT COUNT(*) AS rooms_pending FROM booking WHERE Status_ID = 1";
$sql_approved = "SELECT COUNT(*) AS rooms_approved FROM booking WHERE Status_ID = 4";
$sql_rejected = "SELECT COUNT(*) AS rooms_rejected FROM booking WHERE Status_ID = 3";
$sql_all = "SELECT COUNT(*) AS total_rooms FROM booking";

// ส่งคำสั่ง SQL ไปยังฐานข้อมูล
$result_pending = $conn->query($sql_pending);
$result_approved = $conn->query($sql_approved);
$result_rejected = $conn->query($sql_rejected);
$result_all = $conn->query($sql_all);

// ตรวจสอบผลลัพธ์
if ($result_pending->num_rows > 0) {
    $pending = $result_pending->fetch_assoc();
    $rooms_pending = $pending['rooms_pending'];
} else {
    $rooms_pending = 0;
}

if ($result_approved->num_rows > 0) {
    $approved = $result_approved->fetch_assoc();
    $rooms_approved = $approved['rooms_approved'];
} else {
    $rooms_approved = 0;
}

if ($result_rejected->num_rows > 0) {
    $rejected = $result_rejected->fetch_assoc();
    $rooms_rejected = $rejected['rooms_rejected'];
} else {
    $rooms_rejected = 0;
}

if ($result_all->num_rows > 0) {
    $all = $result_all->fetch_assoc();
    $total_rooms = $all['total_rooms'];
} else {
    $total_rooms = 0;
}

$sql = "SELECT Hall_Name, Dot_Color FROM hall";
$result = $conn->query($sql);
$rooms = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
        $rooms[] = $row;
    }
}
$conn->close();
// สมมุติว่าเราเลือกเอาค่าสีของห้องแรกมาแสดงใน dashboard:
$room_dot_color = isset($rooms[0]['Dot_Color']) ? $rooms[0]['Dot_Color'] : '#ccc';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก</title>
    <link rel="stylesheet" href="../boostarp/css/bootstrap.min.css">
    <link rel="stylesheet" href="../font/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
</head>

<body>

    <?php require_once '../navbar/navbar_main.php'; ?> 

    <div class="full-height">
        <div class="text-center" style="background-color: #010f33;">
            <div class="calendar-header">
                <h2 id="month-year"></h2>
                <div class="navigation">
                    <button id="prev-month" class="btn btn-outline-secondary">
                        < </button>
                            <button id="next-month" class="btn btn-outline-secondary">></button>
                </div>
            </div>
        </div>
        <div class="container-custom-1">
            <table class="calendar">
                <thead>
                    <tr>
                        <th>อา.</th>
                        <th>จ.</th>
                        <th>อ.</th>
                        <th>พ.</th>
                        <th>พฤ.</th>
                        <th>ศ.</th>
                        <th>ส.</th>
                    </tr>
                </thead>
                <tbody id="calendar-body"></tbody>
            </table>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรายละเอียดการจอง -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">รายละเอียดกิจกรรม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bookingModalBody">
                    <!-- รายละเอียดจะถูกเพิ่มที่นี่ -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
    <div class="full-height2">
        <div class="text-center2" style="background-color: #010f33;">
            <div style="font-size: 20px">Dashboard</div>
        </div>
        <div class="container-custom-2">
            <div class="upcoming">
                <div style="display: flex; position: relative;">
                    <div style="padding: 20px; font-size: 30px;">
                        <i class="fa-solid fa-user-check" style="color: #ffffff;"></i>
                    </div>
                    <div style="position: absolute; right: 0; padding: 15px;">
                        <div style="color: #ffffff;">รายการจองของฉัน</div>
                        <div
                            style="display: flex; justify-content: flex-end; padding-top: 10px; font-size: 20px; color: rgb(136, 135, 135);">
                            <?php echo $rooms_pending; ?></div>
                    </div>
                </div>
                <hr>
                <div style="color: #ffffff; font-size: 16px; margin-left: 16px;">รอตรวจสอบ</div>
            </div>
            <div class="activing">
                <div style="display: flex; position: relative;">
                    <div style="padding: 20px; font-size: 30px;">
                        <i class="fa-solid fa-check" style="color: #ffffff;"></i>
                    </div>
                    <div style="position: absolute; right: 0; padding: 15px;">
                        <div style="color: #ffffff;">รายการจองของฉัน</div>
                        <div
                            style="display: flex; justify-content: flex-end; padding-top: 10px; font-size: 20px; color: rgb(136, 135, 135);">
                            <?php echo $rooms_approved; ?></div>
                    </div>
                </div>
                <hr>
                <div style="color: #ffffff; font-size: 16px; margin-left: 16px;">อนุมัติ</div>
            </div>
            <div class="disactiving">
                <div style="display: flex; position: relative;">
                    <div style="padding: 20px; font-size: 30px;">
                        <i class="fa-solid fa-xmark" style="color: #ffffff;"></i>
                    </div>
                    <div style="position: absolute; right: 0; padding: 15px;">
                        <div style="color: #ffffff;">รายการจองของฉัน</div>
                        <div
                            style="display: flex; justify-content: flex-end; padding-top: 10px; font-size: 20px; color: rgb(136, 135, 135);">
                            <?php echo $rooms_rejected; ?></div>
                    </div>
                </div>
                <hr>
                <div style="color: #ffffff; font-size: 16px; margin-left: 16px;">ไม่อนุมัติ</div>
            </div>

            <div class="allbooking">
                <div style="display: flex; position: relative;">
                    <div style="padding: 20px; font-size: 30px;">
                        <i class="fa-solid fa-book" style="color: #ffffff;"></i>
                    </div>
                    <div style="position: absolute; right: 0; padding: 15px;">
                        <div style="color: #ffffff;">สามารถอนุมัติได้</div>
                        <div
                            style="display: flex; justify-content: flex-end; padding-top: 10px; font-size: 20px; color: rgb(136, 135, 135);">
                            <?php echo $rooms_pending; ?></div>
                    </div>
                </div>
                <hr>
                <div style="color: #ffffff; font-size: 16px; margin-left: 16px;">รอตรวจสอบ</div>
            </div>
            <div class="allroom">
                <div style="display: flex; position: relative;">
                    <div style="padding: 20px; font-size: 30px;">
                        <i class="fa-solid fa-building" style="color: #ffffff;"></i>
                    </div>
                    <div style="position: absolute; right: 0; padding: 15px;">
                        <div style="color: #ffffff;">ห้อง</div>
                        <div
                            style="display: flex; justify-content: flex-end; padding-top: 10px; font-size: 20px; color: rgb(136, 135, 135);">
                            <?php echo $total_rooms; ?></div>
                    </div>
                </div>
                <hr>
                <div style="color: #ffffff; font-size: 16px; margin-left: 16px;">ห้องทั้งหมด</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="../boostarp/js/bootstrap.bundle.min.js"></script>
    <script src="../javascript/calendar.js"></script>

</body>
</html>