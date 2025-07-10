<?php
session_start();

// หากล็อกอินแล้ว จะแสดงข้อมูลห้องประชุมได้
require_once '../database/db_connect.php'; 

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../status/main');
    exit;
}

// ดึงข้อมูลจากตาราง hall
$sql = "SELECT Hall_ID, Hall_Name, Hall_Detail, Hall_Size, Capacity, Status_Hall FROM HALL";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// ดึงข้อมูลสมาชิกที่ต้องการแก้ไข
if (isset($_GET['id'])) {
    $personnel_id = $_GET['id'];
    $sql = "SELECT * FROM HALL WHERE Hall_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $Hall_ID);
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
    <title>จองห้อง</title>
    <link rel="stylesheet" href="../boostarp/css/bootstrap.min.css">
    <link rel="stylesheet" href="../font/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="../css/booking.css">
</head>
<body>
    
    <?php require_once '../navbar/navbar_booking.php'; ?>

    <div class="full-height">
        <div class="text-center" style="background-color: #010f33;">
            <div style="font-size: 20px; width: 100%; text-align: left;">รายการห้อง</div>
            <?php
            // ตรวจสอบว่า role_id ของผู้ใช้เป็น Admin หรือไม่
            if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2) {
                echo '<div class="button-container">
                    <button class="add-room btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        <div style="font-size: 15px;">เพิ่มห้อง</div>
                    </button>
                </div>';
            }
            ?>
        </div>
        <div class="container-custom">
            <!-- แสดงข้อความที่นี่ -->
            <?php
                if (isset($_SESSION['message'])) {
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                }
            ?>
            <table id="booking-table" class="table table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ห้อง (room)</th>
                        <th>รายละเอียด</th>
                        <th>สถานะของห้อง</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                // ดึงข้อมูลห้องจากฐานข้อมูล
                $sql = "SELECT Hall_ID, Hall_Name, Hall_Detail, Status_Hall, Hall_Size, Capacity, Dot_Color FROM HALL";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    // ถ้ามีข้อมูลห้องให้แสดงข้อมูลในตาราง
                    $count = 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>
                                <span class='dot' style='display:inline-block; width:10px; height:10px; border-radius:50%; background-color:" . $row['Dot_Color'] . ";'></span>
                            </td>";
                        echo "<td>" . $row['Hall_Name'] . "</td>";
                        echo "<td>" . $row['Hall_Detail'] . "</td>";
                        // แปลง Status_Hall เป็นข้อความ
                        $status_text = "";
                        if ($row['Status_Hall'] == 1) {
                                $status_text = "<span style='color:green'>เปิดการใช้งาน</span>"; // ถ้า Status_Hall เป็น 1 แสดงว่า "เปิด"
                            } else {
                                $status_text = "<span style='color:red'>ปิดปรับปรุง</span>"; // ถ้า Status_Hall เป็น 0 แสดงว่า "ปิด"
                            }
                        echo "<td>" . $status_text . "</td>";
                        echo "<td>";

                        
                        // ตรวจสอบสถานะห้อง ถ้าสถานะเป็น 2 (ปิดการใช้งาน) ซ่อนปุ่มจองห้อง
                        if ($row['Status_Hall'] == 1) {
                            // ถ้าห้องเปิดใช้งาน แสดงปุ่มจอง
                            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                                // ถ้าล็อกอินแล้ว ให้แสดงปุ่มจองห้องที่สามารถคลิกได้
                                echo "<button class='btn btn-outline-dark btn-sm m-1' onclick=\"window.location.href='booking_form.php?hall_id=" . $row['Hall_ID'] . "';\">จองห้อง</button>";
                            } else {
                                // ถ้ายังไม่ได้ล็อกอิน ให้ปุ่มจองพาไปยังหน้าเข้าสู่ระบบ
                                echo "<button class='btn btn-outline-dark btn-sm m-1' onclick=\"alert('กรุณาเข้าสู่ระบบก่อนจองห้อง'); window.location.href='index.php';\">จองห้อง</button>";
                            }
                        } else {
                        }

                        echo "<button class='btn btn-outline-secondary btn-sm' data-bs-toggle='modal' data-bs-target='#roomDetailModal' onclick='loadRoomDetails(" . $row['Hall_ID'] . ")'>รายละเอียด</button>";
                        // แสดงปุ่มแก้ไขห้อง เฉพาะแอดมิน
                        if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2) {
                        echo "<button type='button' class='btn btn-outline-warning editBtn btn-sm m-1' 
                                data-bs-toggle='modal' 
                                data-bs-target='#editRoomModal'
                                data-id='" . $row['Hall_ID'] . "'
                                data-name='" . $row['Hall_Name'] . "'
                                data-detail='" . $row['Hall_Detail'] . "'
                                data-size='" . $row['Hall_Size'] . "'
                                data-capacity='" . $row['Capacity'] . "'
                                data-status='" . $row['Status_Hall'] . "'>
                                <i class='fas fa-edit'></i> แก้ไข
                            </button>";

                        echo "<a href='delete_room.php?id=" . $row['Hall_ID'] . "' class='btn btn-outline-danger btn-sm m-1' onclick='return confirm(\"คุณแน่ใจว่าต้องการลบห้องนี้?\")'>ลบ</a>";
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    // ถ้าไม่มีข้อมูลห้องให้แสดงข้อความแจ้ง
                    echo "<tr><td colspan='4'>ไม่พบข้อมูลห้อง</td></tr>";
                }

                $conn->close();
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal สำหรับเพิ่มห้อง -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoomModalLabel">เพิ่มห้องใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="add_room.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="hall_id" id="hall_id">
                        <div class="mb-3">
                            <label for="hall_name" class="form-label">ชื่อห้อง</label>
                            <input type="text" class="form-control" id="hall_name" name="hall_name" required>
                        </div>
                        <!-- คำอธิบายการจอง -->
                        <div class="mb-3">
                            <label for="hall_detail" class="form-label">รายละเอียดห้อง</label>
                            <textarea id="hall_detail" name="hall_detail" class="form-control" rows="3"
                                required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="hall_size" class="form-label">ขนาดห้อง</label>
                            <input type="text" class="form-control" id="hall_size" name="hall_size" required>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">ความจุ</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="status_hall" class="form-label">สถานะห้อง</label>
                            <select class="form-select" id="status_hall" name="status_hall" required>
                                <option value="1">เปิดการใช้งาน</option>
                                <option value="2">ปิดการใช้งาน</option>
                            </select>
                        </div>
                        <!-- ส่วนเพิ่มการอัปโหลดรูปภาพ -->
                        <div class="mb-3">
                            <label for="hall_image" class="form-label">รูปห้อง</label>
                            <input type="file" class="form-control" id="hall_image" name="hall_image" accept="image/*">
                        </div>

                        <button type="submit" class="btn btn-primary">บันทึกห้อง</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแก้ไขห้อง -->
    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoomModalLabel">แก้ไขห้อง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- เพิ่ม enctype เพื่อรองรับการอัปโหลดไฟล์ -->
                    <form action="edit_room.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="hall_id" id="edit_hall_id">
                        <div class="mb-3">
                            <label for="edit_hall_name" class="form-label">ชื่อห้อง</label>
                            <input type="text" class="form-control" id="edit_hall_name" name="hall_name" required>
                        </div>
                        <!-- คำอธิบายการจอง -->
                        <div class="mb-3">
                            <label for="edit_hall_detail" class="form-label">รายละเอียดห้อง</label>
                            <textarea id="edit_hall_detail" name="hall_detail" class="form-control" rows="3"
                                required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_hall_size" class="form-label">ขนาดห้อง</label>
                            <input type="text" class="form-control" id="edit_hall_size" name="hall_size" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_capacity" class="form-label">ความจุ</label>
                            <input type="number" class="form-control" id="edit_capacity" name="capacity" required
                                min="1">
                        </div>
                        <div class="mb-3">
                            <label for="edit_status_hall" class="form-label">สถานะห้อง</label>
                            <select class="form-select" id="edit_status_hall" name="status_hall" required>
                                <option value="1">เปิดการใช้งาน</option>
                                <option value="2">ปิดการใช้งาน</option>
                            </select>
                        </div>
                        <!-- เพิ่มส่วนอัปโหลดรูปภาพ -->
                        <div class="mb-3">
                            <label for="edit_hall_image" class="form-label">รูปห้อง (ถ้ามีการเปลี่ยนแปลง)</label>
                            <input type="file" class="form-control" id="edit_hall_image" name="hall_image"
                                accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary">บันทึกห้อง</button>
                    </form>
                </div>
            </div>
        </div>
    </div>




    <!-- Modal สำหรับแสดงรายละเอียดห้อง -->
    <div class="modal fade" id="roomDetailModal" tabindex="-1" aria-labelledby="roomDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomDetailModalLabel">รายละเอียดห้องประชุม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBodyContent">
                    <!-- เนื้อหาของรายละเอียดห้องจะถูกโหลดที่นี่ผ่าน JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>



    <!-- JavaScript -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.bootstrap5.js"></script>

    <script>
    $(document).ready(function() {
        $('#booking-table').dataTable();
    });

    $(document).ready(function() {
        // เมื่อคลิกปุ่มแก้ไข
        $('.editBtn').on('click', function() {
            var hallId = $(this).data('id');
            var hallName = $(this).data('name');
            var hallDetail = $(this).data('detail');
            var hallSize = $(this).data('size');
            var capacity = $(this).data('capacity');
            var status = $(this).data('status');

            // ใส่ค่าลงในฟอร์มของ modal
            $('#edit_hall_id').val(hallId);
            $('#edit_hall_name').val(hallName);
            $('#edit_hall_detail').val(hallDetail);
            $('#edit_hall_size').val(hallSize);
            $('#edit_capacity').val(capacity);
            $('#edit_status_hall').val(status);
        });
    });


    function loadRoomDetails(hallId) {
        $.ajax({
            url: 'get_room_details.php',
            type: 'GET',
            data: {
                id: hallId
            },
            success: function(response) {
                $('#modalBodyContent').html(response);
            }
        });
    }

    function bookRoom(hallId) {
        alert("ห้องที่เลือก: " + hallId + " (โปรดเพิ่มฟังก์ชันการจอง)");
    }
    </script>

</body>


</html>