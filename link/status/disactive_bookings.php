<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login/index.php');
    exit;
}

require_once '../database/db_connect.php';

// กำหนด SQL เบื้องต้น
$sql = "SELECT 
            b.Booking_ID, 
            b.Topic_Name, 
            h.Hall_Name, 
            CONCAT(p.First_Name, ' ', p.Last_Name) AS Booker_Name, 
            b.Date_Start, 
            b.Time_Start, 
            b.Time_End, 
            b.Attendee_Count, 
            b.Personnel_ID AS personnel_id,  
            s.Status_Name
        FROM 
            booking b
        LEFT JOIN personnel p ON b.Personnel_ID = p.Personnel_ID
        LEFT JOIN hall h ON b.Hall_ID = h.Hall_ID
        LEFT JOIN booking_status s ON b.Status_ID = s.Status_ID
        WHERE b.Status_ID = 3";

// หากไม่ใช่แอดมิน (เช่น role_id ไม่เท่ากับ 2) ให้แสดงเฉพาะรายการของตัวเอง
if ($_SESSION['role_id'] != 2) {
    $sql .= " AND b.Personnel_ID = '" . $_SESSION['personnel_id'] . "'";
}

$sql .= " ORDER BY b.Booking_ID DESC";

$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน</title>
    <link rel="stylesheet" href="../boostarp/css/bootstrap.min.css">
    <link rel="stylesheet" href="../font/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="../css/disactive_bookings.css">
    <link rel="icon" type="image/png" href="../img/favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="../img/favicon-32x32.png" sizes="32x32"> 
</head>

<body>

    <?php require_once '../navbar/navbar_main.php'; ?>

    <div class="full-height">
        <div class="text-center" style="background-color: #010f33;">
            <div style="font-size: 20px">รายการไม่อนุมัติ</div>
        </div>
        <div class="container-custom">
            <div class="container mt-5">
                <?php
                    if(isset($_SESSION['message'])){
                        echo $_SESSION['message'];
                        unset($_SESSION['message']);
                    }
                ?>
                <?php
                    // ตรวจสอบว่ามีข้อมูลหรือไม่ ถ้าไม่มีให้ซ่อนตารางด้วยการกำหนด style
                    $tableStyle = "";
                    if (!($result && $result->num_rows > 0)) {
                        $tableStyle = "display: none;";
                    }
                ?>
                <table id="member-table" class="table table-striped"<?= $tableStyle ?>">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>หัวข้อ</th>
                            <th>ชื่อห้อง</th>
                            <th>ชื่อผู้จอง</th>
                            <th>วันที่และเวลา</th>
                            <th>จำนวนผู้เข้าร่วม</th>
                            <th>สถานะ</th>
                            <th>เหตุผล</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Booking_ID']; ?></td>
                            <td><?php echo htmlspecialchars($row['Topic_Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Hall_Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Booker_Name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($row['Date_Start']) . ' ' .
                                        htmlspecialchars($row['Time_Start']) . ' - ' .
                                        htmlspecialchars($row['Time_End']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['Attendee_Count']); ?></td>
                            <td>
                                <?php if ($row['Status_Name'] == 'รอตรวจสอบ'): ?>
                                <span class="text-warning">รอตรวจสอบ</span>
                                <?php elseif ($row['Status_Name'] == 'อนุมัติระยะที่สอง'): ?>
                                <span class="text-success">อนุมัติ</span>
                                <?php elseif ($row['Status_Name'] == 'ไม่อนุมัติ'): ?>
                                <span class="text-danger">ไม่อนุมัติ</span>
                                <?php else: ?>
                                <span class="text-muted">ไม่ทราบสถานะ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-outline-dark btn-sm detail-btn"
                                    data-id="<?php echo $row['Booking_ID']; ?>">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <?php if (isset($_SESSION['personnel_id']) && $_SESSION['personnel_id'] == $row['personnel_id']): ?>
                                <a href="../function/delete/delete_booking_disac.php?id=<?php echo $row['Booking_ID']; ?>"
                                    class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('คุณแน่ใจหรือไม่ว่าจะยกเลิกการจองนี้?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรายละเอียดห้อง -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="detailModalLabel">รายละเอียดห้อง</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- เนื้อหาจะถูกโหลดมาจาก room_detail_upcom.php ผ่าน AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>



    <!-- JavaScript -->
    <script src="../boostarp/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.bootstrap5.js"></script>
    <script>
    $(document).ready(function() {
        $('#member-table').DataTable(); // ใช้ DataTables แบบพื้นฐาน
    });

    $(document).ready(function() {
        $('.detail-btn').on('click', function() {
            var bookingId = $(this).data('id');
            // แสดงข้อความกำลังโหลดใน modal body
            $('#detailModal .modal-body').html('Loading...');
            // ทำการ AJAX request ไปยัง room_detail_upcom.php โดยส่ง booking id
            $.ajax({
                url: '../modal/room/room_detail_disac.php',
                type: 'GET',
                data: {
                    id: bookingId
                },
                success: function(response) {
                    $('#detailModal .modal-body').html(response);
                },
                error: function() {
                    $('#detailModal .modal-body').html('Error loading details.');
                }
            });
            // แสดง modal dialog
            var modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();
        });
    });
    </script>




</body>

</html>