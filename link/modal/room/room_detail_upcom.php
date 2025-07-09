<?php
// ไฟล์เชื่อมต่อฐานข้อมูล
include 'db_connect.php';

// ตรวจสอบว่ามีการส่งค่า id ผ่าน GET มาหรือไม่
if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];

    // เตรียมคำสั่ง SQL เพื่อดึงข้อมูลจากตาราง booking
    $sql = "SELECT * FROM booking WHERE Booking_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // ดึงข้อมูลเรคคอร์ดแรก (หากมีหลายแถว คุณสามารถ loop ได้)
        $booking = $result->fetch_assoc();
        ?>

    <!-- เริ่มแสดงผลในรูปแบบ HTML (ตาราง) -->
    <table  class="table table-striped table-bordered" style="margin-bottom: 20px; table-layout: auto; width: 100%;">
        <tr>
            <th style="width: 50%;">หัวข้อ</th>
            <td style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all;"><?php echo htmlspecialchars($booking['Topic_Name']); ?></td>
        </tr>
        <tr>
            <th>วันที่</th>
            <td style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all;"><?php echo htmlspecialchars($booking['Date_Start']); ?></td>
        </tr>
        <tr>
            <th>จำนวนผู้เข้าร่วม</th>
            <td style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all;"><?php echo htmlspecialchars($booking['Attendee_Count']); ?></td>
        </tr>
        <tr>
            <th>รายละเอียดการจอง</th>
            <td style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all;"><?php echo (htmlspecialchars($booking['Booking_Detail'])); ?></td>
        </tr>
        <?php if (!empty($booking['Booking_File_Path'])): ?>
        <tr>
            <th>รูปภาพประกอบ</th>
            <td style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all;">
                <img id="hallImage" src="<?php echo htmlspecialchars($booking['Booking_File_Path']); ?>" alt="Hall Image" class="img-fluid"
                    style="max-width: 250px; height: auto;">
            </td>
        </tr>
        <?php else: ?>
        <tr>
            <th>รูปภาพประกอบ</th>
            <td style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all;">ไม่มีรูปภาพประกอบ</td>
        </tr>
        <?php endif; ?>
    </table>

<?php
    } else {
        echo "ไม่พบข้อมูล (No data found).";
    }

    $stmt->close();
} else {
    echo "ไม่พบรหัสการจอง (No ID specified).";
}
$conn->close();
?>

<!-- Modal สำหรับแสดงรูปภาพขยายใหญ่ -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body p-0">
        <img id="modalImage" src="" class="img-fluid w-100" alt="Enlarged Hall Image">
      </div>
    </div>
  </div>
</div>

<script>
// เมื่อคลิกที่รูปภาพในตาราง
document.getElementById('hallImage').addEventListener('click', function(){
    var modalImage = document.getElementById('modalImage');
    modalImage.src = this.src; // กำหนด src ของรูปใน modal ให้เท่ากับรูปที่คลิก
    var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
    imageModal.show();
});
</script>
