<?php
// เริ่มต้นเซสชันเพื่อเข้าถึงข้อมูลที่เก็บไว้
session_start();

// เคลียร์ข้อมูลในเซสชันทั้งหมดให้ว่าง
$_SESSION = array();

// ทำลายเซสชัน
session_destroy();

// ส่งผู้ใช้กลับไปยังหน้าเข้าสู่ระบบ พร้อมสถานะแจ้งเตือน
header('location: login.php?status=login');
exit();
?>
