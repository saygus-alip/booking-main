<?php
session_start();

// สร้างการเชื่อมต่อ PDO (ปรับค่าตามเซิร์ฟเวอร์ของคุณ)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=booking-main", "root", "alip4523pop", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

include 'auth_check.php';  // ตรวจสอบสิทธิ์การเข้าถึง

// ฟังก์ชันส่งข้อความไปยัง Telegram (ถ้าไม่ต้องการใช้สามารถตัดออกได้)
function sendTelegramMessage($chatId, $message, $botToken) {
    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text'    => $message,
    ];
    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === false) {
        error_log("Error sending message to Telegram.");
        return false;
    }
    error_log("Message sent successfully to Telegram: $result");
    return true;
}

// Token สำหรับ Telegram (ปรับให้ตรงกับของคุณ)
$telegramBotToken = "7668345720:AAGIKyTGFQGUGiMOjbax5Mv9Y30Chydnqc4";

// -----------------------------------------------------------------------
// ดึงข้อมูลห้องประชุม (ตัวอย่างสำหรับแสดงเลือกห้อง)
$sql = "SELECT Hall_ID, Hall_Name, Capacity FROM HALL";
$stmt = $pdo->query($sql);
$halls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// รับค่า hall_id จาก GET หรือ Session
$selected_hall_id = isset($_GET['hall_id'])
    ? $_GET['hall_id']
    : (isset($_SESSION['selected_hall_id']) ? $_SESSION['selected_hall_id'] : null);

$selected_hall_name = '';
$selected_capacity  = 0;

if ($selected_hall_id !== null) {
    try {
        $sql = "SELECT Hall_Name, Capacity FROM HALL WHERE Hall_ID = :hall_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':hall_id' => $selected_hall_id]);
        $hall = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($hall) {
            $selected_hall_name = $hall['Hall_Name'];
            $selected_capacity  = $hall['Capacity'];
        } else {
            error_log("No hall found with ID: " . $selected_hall_id);
        }
    } catch (PDOException $e) {
        error_log("Error fetching hall: " . $e->getMessage());
    }
} else {
    error_log("No selected hall id provided.");
}


// -----------------------------------------------------------------------
// เมื่อผู้ใช้ส่งฟอร์ม (method="POST")
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // รับข้อมูลจากฟอร์ม
    $hall_id        = $_POST['hall_id'];
    $attendees      = $_POST['attendees'];
    $date_start     = $_POST['date_start'];   // ใช้กับ Date_Start_Time
    $time_start     = $_POST['time_start'];   // ใช้กับ Start_Time
    $time_end       = $_POST['time_end'];     // ใช้กับ End_Time
    $topic_name     = $_POST['topic_name'];
    $booking_detail = $_POST['booking_detail'];  // ถ้าต้องการเก็บ ควรเพิ่มคอลัมน์ในตาราง (ตัวอย่างนี้ไม่ใช้)
    $status_id      = 1;  // สมมติ "รอตรวจสอบ"
    $approver_id    = $_SESSION['personnel_id'];
    $approver_stage    = 0;

    // **ตรวจสอบการจองเวลาซ้ำกัน**
    // เงื่อนไข: หากมีการจองในห้องเดียวกัน (hall_id) ในวันที่เดียวกัน (date_start)
    // และช่วงเวลาใหม่มีการทับซ้อนกับช่วงเวลาที่มีอยู่ (time_start < existing time_end และ time_end > existing time_start)
    $conflictSql = "SELECT COUNT(*) AS conflictCount 
                    FROM booking 
                    WHERE Hall_ID = :hall_id 
                      AND Date_Start = :date_start 
                      AND (Time_Start < :time_end AND Time_End > :time_start)";
    $stmtConflict = $pdo->prepare($conflictSql);
    $stmtConflict->execute([
        ':hall_id'    => $hall_id,
        ':date_start' => $date_start,
        ':time_start' => $time_start,
        ':time_end'   => $time_end
    ]);
    $result = $stmtConflict->fetch(PDO::FETCH_ASSOC);
    if ($result['conflictCount'] > 0) {
        $_SESSION['message'] = "<div class='alert alert-danger'>เวลาที่คุณเลือกชนกับการจองที่มีอยู่แล้ว กรุณาเลือกช่วงเวลาใหม่</div>";
        header("Location: booking_form.php");
        exit;
    }
 


    // 3) จัดการไฟล์ที่แนบ (File Upload)
    $uploaded_File_Path = '';
    $file_Type = '';
    $file_Size = 0;
    $uploaded_At = '';

    if (isset($_FILES['booking_file']) && $_FILES['booking_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        // ถ้าไฟล์ PHP อยู่ในโฟลเดอร์ย่อย เช่น login ให้ระบุเป็น "login/uploads/" ตามความเหมาะสม
        $fileName = basename($_FILES['booking_file']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExtension, $allowedTypes)) {
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $targetFilePath = $uploadDir . $newFileName;
            if (move_uploaded_file($_FILES['booking_file']['tmp_name'], $targetFilePath)) {
                $uploaded_File_Path = $targetFilePath;
                $file_Type = $_FILES['booking_file']['type'];
                $file_Size = $_FILES['booking_file']['size'];
                $uploaded_At = date("Y-m-d H:i:s");
            } else {
                $_SESSION['message'] = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการอัปโหลดไฟล์</div>";
                header("Location: booking_form.php");
                exit;
            }
        } else {
            $_SESSION['message'] = "<div class='alert alert-danger'>ประเภทไฟล์ไม่ถูกต้อง (อนุญาตเฉพาะ JPG, JPEG, PNG, GIF)</div>";
            header("Location: booking_form.php");
            exit;
        }
    }

    // 4) INSERT ข้อมูลการจองลงในตาราง booking
    $sql = "INSERT INTO booking (
        Personnel_ID,
        Date_Start,
        Time_Start,
        Time_End,
        Hall_ID,
        Equipment_ID,
        Attendee_Count,
        Booking_Detail,
        Status_ID,
        Approver_ID,
        Topic_Name,
        Approval_Stage,
        Booking_File_Path,
        File_Type,
        File_Size,
        Uploaded_At
    ) VALUES (
        :personnel_id,
        :date_start,
        :time_start,
        :time_end,
        :hall_id,
        :equipment_id,
        :attendee_count,
        :booking_detail,
        :status_id,
        :approver_id,
        :topic_name,
        :approval_stage,
        :booking_file_path,
        :file_type,
        :file_size,
        :uploaded_at
    )";

    $stmt = $pdo->prepare($sql);
    $params = [
        ':personnel_id'             => $_SESSION['personnel_id'],
        ':date_start'               => $date_start,          // ตรวจสอบรูปแบบวันที่
        ':time_start'               => $time_start,
        ':time_end'                 => $time_end,
        ':hall_id'                  => $hall_id,
        ':equipment_id'             => $equipment_id,
        ':attendee_count'           => $attendees,
        ':booking_detail'           => $booking_detail,
        ':status_id'                => $status_id,
        ':approver_id'              => $approver_id,
        ':topic_name'               => $topic_name,
        ':approval_stage'           => $approval_stage,
        ':booking_file_path'        => $uploaded_File_Path,
        ':file_type'                => $file_Type,
        ':file_size'                => $file_Size,
        ':uploaded_at'              => $uploaded_At
    ];

    try {
        $stmt->execute($params);
    } catch (PDOException $e) {
        die("Error executing booking insert: " . $e->getMessage());
    }

    // ดึงข้อมูล Telegram_ID ของผู้ที่มี role_id = 2 หรือ role_id = 4
    $stmt = $pdo->prepare("SELECT Telegram_ID FROM personnel WHERE role_id IN (2,4)");
    $stmt->execute();
    $telegramRecipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Selected Hall Name: " . $selected_hall_name); // Debug: ตรวจสอบค่า hall name

    $message = "มีการจองห้องประชุมใหม่:\n"
        . "หัวข้อ: $topic_name\n"
        . "จำนวนผู้เข้าประชุม: $attendees\n"
        . "เริ่มเวลา: $date_start $time_start\n"
        . "สิ้นสุดเวลา: $date_start $time_end";

    // ส่งข้อความไปยังแต่ละ Telegram_ID
    foreach ($telegramRecipients as $recipient) {
        $telegram_id = $recipient['Telegram_ID'];
        sendTelegramMessage($telegram_id, $message, $telegramBotToken);
    }

    $_SESSION['message'] = "<div class='alert alert-success'>การจองห้องประชุมเสร็จสมบูรณ์</div>";
    header("Location: booking.php");
    exit;
}
?>






<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มการจอง</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
    @media (max-width: 991px) {
        #navbarNav {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

    }

    html,
    body {
        margin: 0;
        padding: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    body {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    main {
        flex-grow: 1;
        /* ให้ส่วนเนื้อหาขยายเต็มพื้นที่ที่เหลือ */
        overflow: auto;
        padding-bottom: 20px;
    }

    /* Navbar brand logo */
    .navbar-brand .responsive-img {
        max-width: 100%;
        height: auto;
    }

    /* Adjusting padding for Navbar */
    .navbar {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    /* Adjust padding for nav-link */
    .nav-link {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    /* Dropdown menu styling */
    .nav-item .dropdown-menu {
        background-color: rgb(1, 20, 69);
        color: #ffffff;
    }

    .dropdown-menu .dropdown-item {
        color: #ffffff;
    }

    .dropdown-menu .dropdown-item:hover {
        background-color: #e0a444;
        color: #ffffff;
    }

    .full-height {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        height: calc(100vh - 56px);
        position: relative;
        padding-top: 60px;
        padding-bottom: 20px;
        flex-grow: 1;
        overflow: auto;
        padding-bottom: 20px;
    }

    .text-center {
        color: white;
        padding: 20px;
        border-radius: 5px 5px 0 0;
        height: 70px;
        display: flex;
        align-items: center;
        border: 1px solid #e0e0e0;
        max-width: 1200px;
        width: 100%;

    }

    .container-custom {
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        max-width: 1200px;
        width: 100%;
        margin-bottom: 20px;
    }

    .footer {
        width: 100%;
        background-color: #f8f9fa;
        padding: 20px;
        font-size: 16px;
        color: #6c757d;
        margin-top: auto;
        position: relative;
    }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark p-3" style="background-color: #010f33;">
        <div class="container-fluid">
            <a href="main.php" class="navbar-brand d-flex align-items-center">
                <img class="responsive-img" src="LOGO.png" alt="system booking" width="45" height="45">
                <span class="ms-3">ระบบจองห้องประชุม</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a href="main.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'main.php') ? 'active' : ''; ?>">หน้าหลัก</a>
                    </li>

                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>

                    <!-- Dropdown เมนูสำหรับ "รายการจองของฉัน" -->
                    <li class="nav-item dropdown">
                        <!-- เช็คไฟล์ PHP สำหรับ active -->
                        <a class="nav-link dropdown-toggle <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['upcoming_bookings.php', 'disactive_bookings.php', 'active_bookings.php']) ? 'active' : ''); ?>"
                            href="#" id="myBookingsDropdown" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            รายการจองของฉัน
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="myBookingsDropdown">
                            <li><a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'upcoming_bookings.php') ? 'active' : ''; ?>"
                                    href="upcoming_bookings.php">รอตรวจสอบ</a></li>
                            <li><a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'active_bookings.php') ? 'active' : ''; ?>"
                                    href="active_bookings.php">อนุมัติ</a></li>
                            <li><a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'disactive_bookings.php') ? 'active' : ''; ?>"
                                    href="disactive_bookings.php">ไม่อนุมัติ</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="booking.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'booking.php') ? 'active' : ''; ?>">จองห้อง</a>
                    </li>

                    <?php if ($_SESSION['role_id'] == 2): ?>
                    <li class="nav-item">
                        <a href="members.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'members.php') ? 'active' : ''; ?>">สมาชิก</a>
                    </li>
                    <li class="nav-item">
                        <a href="reports.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">รายงาน</a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">สถิติ</a>
                    </li>
                    <?php elseif ($_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 4): ?>
                    <li class="nav-item">
                        <a href="reports.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">รายงาน</a>
                    </li>
                    <?php endif; ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            สวัสดี, <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="edit_profile.php">แก้ไขข้อมูล</a></li>
                            <li><a class="dropdown-item" href="logout.php">ออกจากระบบ</a></li>
                        </ul>
                    </li>

                    <?php else: ?>
                    <li class="nav-item">
                        <a href="booking.php" class="nav-link active">จองห้อง</a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">เข้าสู่ระบบ</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="full-height">
        <div class="text-center" style="background-color: #010f33;">
            <div style="font-size: 20px">เพิ่มการจองห้อง</div>
        </div>
        <div class="container-custom">
            <form action="booking_form.php" method="POST" enctype="multipart/form-data">

                <!-- แสดงข้อความที่นี่ -->
                <?php
                if (isset($_SESSION['message'])) {
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                }
                ?>

                <!-- ห้องที่เลือก (แสดงให้ดู แต่ไม่สามารถแก้ไขได้) -->
                <div class="mb-3">
                    <label for="hall_id" class="form-label">ห้องประชุม</label>
                    <input type="text" class="form-control" id="hall_id"
                        value="<?php echo htmlspecialchars($selected_hall_name); ?>" readonly>
                    <!-- ส่งค่า hall_id ไปยัง backend -->
                    <input type="hidden" name="hall_id" value="<?php echo $selected_hall_id; ?>">
                </div>

                <!-- หัวข้อการจอง -->
                <div class="mb-3">
                    <label for="topic_name" class="form-label">หัวข้อการจอง</label>
                    <input type="text" id="topic_name" name="topic_name" class="form-control" required>
                </div>

                <!-- จำนวนผู้เข้าประชุม -->
                <div class="mb-3">
                    <label for="attendees" class="form-label">จำนวนผู้เข้าประชุม</label>
                    <input type="number" id="attendees" name="attendees" class="form-control" required min="1"
                        max="<?php echo $selected_capacity; ?>">
                    <small class="text-muted">ความจุสูงสุด: <?php echo $selected_capacity; ?> คน</small>
                </div>

                <!-- วันที่เริ่มต้น -->
                <div class="mb-3">
                    <label for="date_start" class="form-label">วันที่จอง</label>
                    <input type="date" id="date_start" name="date_start" class="form-control" required>
                </div>

                <!-- เวลาเริ่มต้น -->
                <div class="mb-3">
                    <label for="time_start" class="form-label">เวลาเริ่มต้น</label>
                    <input type="time" id="time_start" name="time_start" class="form-control" required>
                </div>

                <!-- เวลาสิ้นสุด -->
                <div class="mb-3">
                    <label for="time_end" class="form-label">เวลาสิ้นสุด</label>
                    <input type="time" id="time_end" name="time_end" class="form-control" required>
                </div>

                <!-- เพิ่ม Input สำหรับอัปโหลดไฟล์/รูปภาพ -->
                <div class="mb-3">
                    <label for="booking_file" class="form-label">แนบรูปแบบการจัด</label>
                    <input type="file" id="booking_file" name="booking_file" class="form-control" accept="image/*">
                </div>

                <!-- คำอธิบายการจอง -->
                <div class="mb-3">
                    <label for="booking_detail" class="form-label">รูปแบบการจัดและอุปกรณ์ที่ใช้</label>
                    <textarea id="booking_detail" name="booking_detail" class="form-control" rows="3"
                        required></textarea>
                </div>

                <button type="submit" class="btn btn-dark">บันทึกการจอง</button>
            </form>

        </div>

    </div>




    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
    // ดึงวันที่ปัจจุบัน
    let today = new Date();
    let now = new Date();

    // แปลงเป็นรูปแบบ YYYY-MM-DD
    let yyyy = today.getFullYear();
    let mm = String(today.getMonth() + 1).padStart(2, '0'); // เดือนต้องเพิ่ม 1 เพราะเดือนเริ่มจาก 0
    let dd = String(today.getDate()).padStart(2, '0'); // วันต้องเติม 0 ข้างหน้า

    let currentDate = `${yyyy}-${mm}-${dd}`;
    document.getElementById("date_start").value = currentDate;
    </script>



</body>

</html>