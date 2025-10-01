<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../../login/index');
    exit;
}

require_once '../../database/db_connect.php'; 

// ดึงข้อมูลของผู้ใช้ที่ล็อกอินจากตาราง personnel
$personnel_id = $_SESSION['personnel_id'];
$sql = "SELECT First_Name, Last_Name, Email, Phone, Telegram_ID, Position_ID, Subject_Group_ID, Role_ID FROM personnel WHERE Personnel_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $personnel_id);
$stmt->execute();
$stmt->bind_result($first_name, $last_name, $email, $phone, $telegram_id, $position_id, $subject_group_id, $role_id);
$stmt->fetch();
$stmt->close();

// ดึงข้อมูลตำแหน่ง
$positions = [];
$position_query = "SELECT Position_ID, Position_Name FROM position";
$result = $conn->query($position_query);
while ($row = $result->fetch_assoc()) {
    $positions[] = $row;
}

// ดึงข้อมูลสถานะ
$roles = [];
$role_query = "SELECT Role_ID, Role_Name FROM role";
$result = $conn->query($role_query);
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
}

// ดึงข้อมูลกลุ่มสาระการเรียนรู้
$subject_groups = [];
$subject_group_query = "SELECT Subject_Group_ID, Subject_Group_Name FROM subject_group";
$result = $conn->query($subject_group_query);
while ($row = $result->fetch_assoc()) {
    $subject_groups[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม (แม้จะเป็นค่าว่างก็รับมา)
    $posted_first_name   = isset($_POST['first_name']) ? $_POST['first_name'] : '';
    $posted_last_name    = isset($_POST['last_name']) ? $_POST['last_name'] : '';
    $posted_email        = isset($_POST['email']) ? $_POST['email'] : '';
    $posted_phone        = isset($_POST['phone']) ? $_POST['phone'] : '';
    $posted_telegram_id  = isset($_POST['telegram_id']) ? $_POST['telegram_id'] : '';
    $posted_new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $posted_confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // ตรวจสอบกรณีรหัสผ่าน ถ้ามีการกรอก (แม้จะเป็นค่าว่างก็ถือว่าไม่ต้องอัปเดต)
    if ($posted_new_password !== '') {
        if ($posted_new_password !== $posted_confirm_password) {
            echo "<script>
                    alert('รหัสผ่านไม่ตรงกัน!');
                    window.location.href='edit_profile.php';
                  </script>";
            exit;
        }
    }

    // ตรวจสอบข้อมูลซ้ำแบบแยกฟิลด์
    // สำหรับ Email หากมีการเปลี่ยนแปลงและไม่เป็นค่าว่าง
    if ($posted_email !== $email && $posted_email !== '') {
        $stmt = $conn->prepare("SELECT Personnel_ID FROM personnel WHERE Email = ? AND Personnel_ID != ?");
        $stmt->bind_param('si', $posted_email, $personnel_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>
                    alert('อีเมลนี้มีผู้ใช้งานแล้ว!');
                    window.location.href='edit_profile.php';
                  </script>";
            exit;
        }
        $stmt->close();
    }

    // สำหรับ Telegram ID หากมีการเปลี่ยนแปลงและไม่เป็นค่าว่าง
    if ($posted_telegram_id !== $telegram_id && $posted_telegram_id !== '') {
        $stmt = $conn->prepare("SELECT Personnel_ID FROM personnel WHERE Telegram_ID = ? AND Personnel_ID != ?");
        $stmt->bind_param('si', $posted_telegram_id, $personnel_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>
                    alert('Telegram ID นี้มีผู้ใช้งานแล้ว!');
                    window.location.href='edit_profile';
                  </script>";
            exit;
        }
        $stmt->close();
    }

    // สร้างอาร์เรย์สำหรับเก็บ field ที่มีการเปลี่ยนแปลง (แม้จะเป็นค่าว่าง) และค่าที่จะ bind
    $updateFields = [];
    $params = [];
    $paramTypes = "";

    if ($posted_first_name !== $first_name) {
        $updateFields[] = "First_Name = ?";
        $params[] = $posted_first_name;
        $paramTypes .= "s";
    }

    if ($posted_last_name !== $last_name) {
        $updateFields[] = "Last_Name = ?";
        $params[] = $posted_last_name;
        $paramTypes .= "s";
    }

    if ($posted_email !== $email) {
        $updateFields[] = "Email = ?";
        $params[] = $posted_email;
        $paramTypes .= "s";
    }

    if ($posted_phone !== $phone) {
        $updateFields[] = "Phone = ?";
        $params[] = $posted_phone;
        $paramTypes .= "s";
    }

    if ($posted_telegram_id !== $telegram_id) {
        $updateFields[] = "Telegram_ID = ?";
        $params[] = $posted_telegram_id;
        $paramTypes .= "s";
    }

    if ($posted_new_password !== '') {
        $updateFields[] = "Password = ?";
        $hashed_password = password_hash($posted_new_password, PASSWORD_DEFAULT);
        $params[] = $hashed_password;
        $paramTypes .= "s";
    }

    // หากไม่มีการเปลี่ยนแปลงใด ๆ
    if (empty($updateFields)) {
        echo "<script>
                alert('ไม่มีการเปลี่ยนแปลงข้อมูล!');
                window.location.href='edit_profile';
              </script>";
        exit;
    }

    // สร้างคำสั่ง SQL แบบไดนามิกสำหรับอัปเดตข้อมูล
    $sql = "UPDATE personnel SET " . implode(", ", $updateFields) . " WHERE Personnel_ID = ?";
    $params[] = $personnel_id;
    $paramTypes .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($paramTypes, ...$params);

    if ($stmt->execute()) {
        // อัปเดต session สำหรับข้อมูลที่เปลี่ยนแปลง
        if ($posted_first_name !== $first_name) {
            $_SESSION['first_name'] = $posted_first_name;
        }
        if ($posted_last_name !== $last_name) {
            $_SESSION['last_name'] = $posted_last_name;
        }
        if ($posted_phone !== $phone) {
            $_SESSION['phone'] = $posted_phone;
        }
        if ($posted_telegram_id !== $telegram_id) {
            $_SESSION['telegram_id'] = $posted_telegram_id;
        }

        echo "<script>
                alert('ข้อมูลอัปเดตเรียบร้อยแล้ว!');
                window.location.href='edit_profile';
              </script>";
        exit;
    } else {
        echo "<script>
                alert('เกิดข้อผิดพลาด: " . addslashes($stmt->error) . "');
                window.location.href='edit_profile';
              </script>";
        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลผู้ใช้</title>
    <link rel="stylesheet" href="../../boostarp/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../font/css/all.min.css">
    <link rel="stylesheet" href="../../css/edit_profile.css">
    <link rel="icon" type="image/png" href="../../img/favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="../../img/favicon-32x32.png" sizes="32x32">

</head>

<body>

    <?php require_once '../../navbar/navbar_member.php'; ?>

    <div class="full-height">
        <div class="text-center" style="background-color: #010f33;">
            <div style="font-size: 20px">แก้ไขข้อมูลผู้ใช้งาน</div>
        </div>
        <div class="container container-custom">
            <form action="edit_profile.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">อีเมล</label>
                    <input type="email" id="email" class="form-control" name="email" value="<?php echo $email; ?>"
                        readonly>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                    <input type="password" id="new_password" class="form-control" name="new_password"
                        placeholder="รหัสผ่านต้องไม่น้อยกว่า 4 ตัวอักษร">
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                    <input type="password" id="confirm_password" class="form-control" name="confirm_password"
                        placeholder="กรอกรหัสผ่านอีกครั้ง">
                </div>
                <div class="mb-3">
                    <label for="first_name" class="form-label">ชื่อจริง</label>
                    <input type="text" id="first_name" class="form-control" name="first_name"
                        placeholder="แก้ไขชื่อของคุณ" value="<?php echo $first_name; ?>">
                </div>
                <div class="mb-3">
                    <label for="last_name" class="form-label">นามสกุล</label>
                    <input type="text" id="last_name" class="form-control" name="last_name"
                        placeholder="แก้ไขนามสกุลของคุณ" value="<?php echo $last_name; ?>">
                </div>
                <div class="mb-3">
                    <label for="position_id" class="form-label">ตำแหน่ง</label>
                    <select class="selectpicker form-select" data-live-search="true" id="position_id" name="position_id"
                        disabled>
                        <?php foreach ($positions as $position): ?>
                        <option value="<?php echo $position['Position_ID']; ?>"
                            <?php if ($position['Position_ID'] == $position_id) echo 'selected'; ?>>
                            <?php echo $position['Position_Name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="subject_group_id" class="form-label">กลุ่มสาระการเรียนรู้</label>
                    <select class="selectpicker form-select" data-live-search="true" id="subject_group_id"
                        name="subject_group_id" disabled>
                        <?php foreach ($subject_groups as $group): ?>
                        <option value="<?php echo $group['Subject_Group_ID']; ?>"
                            <?php if ($group['Subject_Group_ID'] == $subject_group_id) echo 'selected'; ?>>
                            <?php echo $group['Subject_Group_Name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="role_id" class="form-label">สถานะสมาชิก</label>
                    <select class="selectpicker form-select" data-live-search="true" id="role_id" name="role_id"
                        disabled>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['Role_ID']; ?>"
                            <?php if ($role['Role_ID'] == $role_id) echo 'selected'; ?>>
                            <?php echo $role['Role_Name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="telegram_id" class="form-label">Telegram ID</label>
                    <input type="number" id="telegram_id" class="form-control" name="telegram_id" value="<?php echo $telegram_id; ?>"
                        >
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                    <input type="number" id="phone" class="form-control" name="phone" value="<?php echo $phone; ?>"
                        >
                </div>

                <button class="btn btn-outline-dark">บันทึกข้อมูลผู้ใช้ใหม่</button>
            </form>
        </div>
    </div>



    <script src="../../boostarp/js/bootstrap.bundle.min.js"></script>

</body>

</html>