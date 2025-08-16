<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../../login/index');
    exit;
}

// หากล็อกอินแล้ว จะแสดงข้อมูลสมาชิกได้
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

// ดึงข้อมูลสมาชิกที่ต้องการแก้ไข
if (isset($_GET['id'])) {
    $personnel_id = $_GET['id'];
    $sql = "SELECT * FROM personnel WHERE personnel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $personnel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมาชิก</title>
    <link rel="stylesheet" href="../../boostarp/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../font/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="../../css/member.css">

</head>

<body>

    <?php require_once '../../navbar/navbar_member.php'; ?>

    <div class="full-height">
        <div class="text-center" style="background-color: #010f33;">
            <div style="font-size: 20px; width: 100%; text-align: left;">รายการสมาชิก</div>
            <div class="button-container">
                <button class="add-user btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <div style="font-size: 15px;">เพิ่มผู้ใช้งาน</div>
                </button>
            </div>
        </div>
        <div class="container-custom">
            <!-- แสดงข้อความที่นี่ -->
            <?php
            if (isset($_SESSION['message'])) {
                echo $_SESSION['message'];
                unset($_SESSION['message']);
            }
            ?>
            <table id="member-table" class="table table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ชื่อ (Firstname)</th>
                        <th>นามสกุล (Lastname)</th>
                        <th>ชื่อผู้ใช้ (Username)</th>
                        <th>ตำแหน่ง (Position)</th>
                        <th>สถานะ (Role)</th>
                        <th>เหตุผล (Reason)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // ดึงข้อมูลสมาชิกจากฐานข้อมูล
                    $sql = "SELECT personnel_id, first_name, last_name, username, position_id, role_id, subject_group_id, phone, email, telegram_id, password FROM personnel";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        // ถ้ามีข้อมูลสมาชิกให้แสดงข้อมูลในตาราง
                        $count = 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $count++ . "</td>";
                            echo "<td>" . $row['first_name'] . "</td>";
                            echo "<td>" . $row['last_name'] . "</td>";
                            echo "<td>" . $row['username'] . "</td>";
                            // กำหนดตำแหน่งเป็นข้อความที่เข้าใจง่าย
                            $position_id = "";
                            switch ($row['position_id']) {
                                case 1:
                                    $position_id = "ผู้บริหาร";
                                    break;
                                case 2:
                                    $position_id = "ครู";
                                    break;
                                case 3:
                                    $position_id = "บุคคาลากร";
                                    break;
                            }
                            echo "<td>" . $position_id . "</td>";

                            $role_id = "";
                            switch ($row['role_id']) {
                                case 1:
                                    $role_id = "ผู้ใช้";
                                    break;
                                case 2:
                                    $role_id = "Admin";
                                    break;
                                case 3:
                                    $role_id = "ผู้อนุมัติ";
                                    break;
                                case 4:
                                    $role_id = "รอง";
                                    break;
                            }
                            echo "<td>" . $role_id . "</td>";
                            // เพิ่มลิงก์แก้ไขในตาราง
                            echo "<td>
                                <button type='button' class='btn btn-outline-warning btn-sm editBtn' 
                                      data-bs-toggle='modal' 
                                      data-bs-target='#editUserModal'
                                      data-id='" . $row['personnel_id'] . "'
                                      data-first_name='" . $row['first_name'] . "'
                                      data-last_name='" . $row['last_name'] . "'
                                      data-username='" . $row['username'] . "'
                                      data-position='" . $row['position_id'] . "'
                                      data-phone='" . $row['phone'] . "'
                                      data-subject_group='" . $row['subject_group_id'] . "'
                                      data-role='" . $row['role_id'] . "'
                                      data-telegram='" . $row['telegram_id'] . "'
                                      data-email='" . $row['email'] . "'>
                                      
                                      <i class='fas fa-edit'></i> แก้ไข
                                </button>
                                <a href='delete_member.php?id=" . $row['personnel_id'] . "' class='btn btn-outline-danger btn-sm' onclick='return confirm(\"คุณแน่ใจว่าต้องการลบผู้ใช้งานนี้?\")'>ลบ</a>
                            </td>";

                            echo "</tr>";
                        }            
                    } else {
                        // ถ้าไม่มีข้อมูลสมาชิกให้แสดงข้อความแจ้ง
                        echo "<tr><td colspan='5'>ไม่พบข้อมูลสมาชิก</td></tr>";
                    }

                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal สำหรับเพิ่มผู้ใช้งาน -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">เพิ่มผู้ใช้งานใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="add_user.php" method="post">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้ (ห้ามซ้ำ)</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">สถานะ</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="1">ผู้ใช้</option>
                                <option value="2">Admin</option>
                                <option value="3">ผู้อนุมัติ</option>
                                <option value="4">รอง</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subject_group_id" class="form-label">กลุ่มสาระการเรียนรู้</label>
                            <select name="subject_group_id" id="subject_group_id" class="form-select" required>
                                <option value="1">กลุ่มสาระการเรียนรู้ภาษาไทย</option>
                                <option value="2">กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม</option>
                                <option value="3">กลุ่มสาระการเรียนรู้คณิตศาสตร์</option>
                                <option value="4">กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี</option>
                                <option value="5">กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ</option>
                                <option value="6">กลุ่มสาระการงานอาชีพและเทคโนโลยี</option>
                                <option value="7">กลุ่มสาระศิลปะ ดนตรี นาฏศิลป์</option>
                                <option value="8">กลุ่มสาระสุขศึกษา พลศึกษา</option>
                                <option value="9">แนะแนวและห้องสมุด</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">เบอร์โทรศัพท์ (ห้ามซ้ำ)</label>
                            <input type="number" name="phone" id="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">G-mail (ห้ามซ้ำ)</label>
                            <input type="email" name="email" class="form-control" id="email">
                        </div>
                        <div class="mb-3">
                            <label for="telegram_id" class="form-label">ID Telegram (ห้ามซ้ำ)</label>
                            <input type="number" name="telegram_id" class="form-control" id="telegram_id">
                        </div>
                        <div class="mb-3">
                            <label for="position_id" class="form-label">บทบาท</label>
                            <select name="position_id" class="form-select" id="position_id" required>
                                <option value="1">ผู้บริหาร</option>
                                <option value="2">ครู</option>
                                <option value="3">บุคลากรทางการศึกษา</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแก้ไขผู้ใช้งาน -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">แก้ไขข้อมูลผู้ใช้งาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- แบบฟอร์มกรอกข้อมูลผู้ใช้งาน -->
                    <form action="edit_user.php" method="post">
                        <input type="hidden" name="personnel_id" id="edit_personnel_id">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name">
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name">
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้ (ห้ามซ้ำ)</label>
                            <input type="text" class="form-control" id="edit_username" name="username">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">สถานะ</label>
                            <select class="form-select" id="edit_role_id" name="role_id">
                                <option value="1">ผู้ใช้</option>
                                <option value="2">Admin</option>
                                <option value="3">ผู้อนุมัติ</option>
                                <option value="4">รอง</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subject_group_id" class="form-label">กลุ่มสาระการเรียนรู้</label>
                            <select name="subject_group_id" id="edit_subject_group_id" class="form-select">
                                <option value="1">กลุ่มสาระการเรียนรู้ภาษาไทย</option>
                                <option value="2">กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม</option>
                                <option value="3">กลุ่มสาระการเรียนรู้คณิตศาสตร์</option>
                                <option value="4">กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี</option>
                                <option value="5">กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ</option>
                                <option value="6">กลุ่มสาระการงานอาชีพและเทคโนโลยี</option>
                                <option value="7">กลุ่มสาระศิลปะ ดนตรี นาฏศิลป์</option>
                                <option value="8">กลุ่มสาระสุขศึกษา พลศึกษา</option>
                                <option value="9">แนะแนวและห้องสมุด</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">เบอร์โทรศัพท์ (ห้ามซ้ำ)</label>
                            <input type="number" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">G-mail (ห้ามซ้ำ)</label>
                            <input type="email" name="email" class="form-control" id="edit_email">
                        </div>
                        <div class="mb-3">
                            <label for="telegram_id" class="form-label">ID Telegram (ห้ามซ้ำ)</label>
                            <input type="number" name="telegram_id" class="form-control" id="edit_telegram_id">
                        </div>
                        <div class="mb-3">
                            <label for="position_id" class="form-label">บทบาท</label>
                            <select name="position_id" class="form-select" id="edit_position_id">
                                <option value="1">ผู้บริหาร</option>
                                <option value="2">ครู</option>
                                <option value="3">บุคลากรทางการศึกษา</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- JavaScript -->
    <script src="../../boostarp/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.bootstrap5.js"></script>

    <script>
    $(document).ready(function() {
        $('#member-table').dataTable();
    });

    // เมื่อคลิกปุ่มแก้ไข ให้ดึงข้อมูลจาก data attributes ไปใส่ใน modal แก้ไข
    $('.editBtn').on('click', function(){
              var id = $(this).data('id');
              var firstName = $(this).data('first_name');
              var lastName = $(this).data('last_name');
              var username = $(this).data('username');
              var position = $(this).data('position');
              var subjectGroup = $(this).data('subject_group');
              var role = $(this).data('role');
              var telegramId = $(this).data('telegram');
              var email = $(this).data('email');  // ดึงข้อมูลอีเมล
              var phone = $(this).data('phone');

              $('#edit_personnel_id').val(id);
              $('#edit_first_name').val(firstName);
              $('#edit_last_name').val(lastName);
              $('#edit_username').val(username);
              $('#edit_position_id').val(position);
              $('#edit_subject_group_id').val(subjectGroup);
              $('#edit_role_id').val(role);
              $('#edit_telegram_id').val(telegramId);
              $('#edit_email').val(email); // แสดงค่าอีเมลในฟิลด์
              $('#edit_phone').val(phone);
          });


    </script>
    

</body>
</html>