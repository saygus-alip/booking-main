<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $hall_id = $_GET['id'];

    // ดึงข้อมูลห้องจากฐานข้อมูล
    $sql = "SELECT * FROM hall WHERE Hall_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hall_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hall = $result->fetch_assoc();

    if ($hall) {
        echo "<table class='table table-striped table-bordered' style='margin-bottom: 20px; table-layout: auto; width: 100%;'>";
        echo "<tr><th>ชื่อห้อง</th><td>" . $hall['Hall_Name'] . "</td></tr>";
        echo "<tr><th>รายละเอียด</th><td>" . $hall['Hall_Detail'] . "</td></tr>";
        echo "<tr><th>ขนาดห้อง</th><td>" . $hall['Hall_Size'] . "</td></tr>";
        echo "<tr><th>ความจุ</th><td>" . $hall['Capacity'] . " คน</td></tr>";

        // แสดงรูปห้อง (ถ้าคอลัมน์ Hall_Image เก็บ path รูปภาพ)
        // ตรวจสอบว่ามีรูปภาพหรือไม่
        if (!empty($hall['Hall_Image'])) {
            echo "<tr>
                    <th>รูปห้อง</th>
                    <td>
                        <img id='hallImage' src='" . $hall['Hall_Image'] . "' 
                             alt='Hall Image' 
                             class='img-fluid' 
                             style='max-width:250px;cursor:pointer;'>
                    </td>
                  </tr>";
        } else {
            echo "<tr><th>รูปห้อง</th><td>ไม่มีรูปภาพ</td></tr>";
        }
        

        echo "</table>";
    } else {
        echo "ไม่พบข้อมูล";
    }
} else {
    echo "ไม่พบข้อมูล";
}
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

