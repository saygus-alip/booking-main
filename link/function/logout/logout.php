<?php
session_start();
session_unset();
session_destroy();

// ถ้าไม่ได้เลือก "จำการเข้าสู่ระบบ" จะลบ cookie
if (!isset($_POST['remember'])) {
    setcookie('username', '', time() - 3600, "/"); // ลบ cookie
    setcookie('role_id', '', time() - 3600, "/");
}

// กลับไปที่หน้า index.php
header('Location: index.php');
exit;
?>
