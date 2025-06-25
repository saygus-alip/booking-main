<?php
session_start();

// หากล็อกอินแล้ว จะแสดงข้อมูลห้องประชุมได้
include 'db_connect.php';
include 'auth_check.php'; // เรียกใช้งานการตรวจสอบการเข้าสู่ระบบและสถานะผู้ใช้

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
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
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

    .container-custom {
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        max-width: 1200px;
        width: 100%;
        margin-bottom: 20px;
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

    .footer {
        width: 100%;
        background-color: #f8f9fa;
        padding: 20px;
        font-size: 16px;
        color: #6c757d;
        margin-top: auto;
        position: relative;
    }

    table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        overflow: hidden;
    }

    .table td,
    .table th {
        text-align: center;
        /* จัดกึ่งกลางแนวนอน */
        vertical-align: middle;
        /* จัดกึ่งกลางแนวตั้ง */
    }

    th,
    td {
        border: 1px solid #e0e0e0;
        padding: 20px;
    }

    th {
        background-color: #f8f9fa;
    }

    td {
        background-color: #ffffff;
    }

    td img {
        border-radius: 5px;
        /* กำหนดความโค้งให้กับรูปภาพ */
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

    .add-room {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .button-container {
        display: flex;
        justify-content: flex-end;
        /* ทำให้ปุ่มไปอยู่ทางขวา */
        width: 100%;
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