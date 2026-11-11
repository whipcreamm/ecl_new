<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include 'config.php';

// ถ้าล็อกอินอยู่แล้ว ไม่จำเป็นต้องเข้าหน้านี้ ให้ไปหน้า index เลย
if (isset($_SESSION['company_id'])) {
    header('location: index.php');
    exit();
}

$status = '';
$email_input = '';

// ตรวจสอบการส่งข้อมูลแบบ POST จากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. รับค่าอีเมลและตัดช่องว่างหัวท้าย
    $email_input = trim($_POST['email']);
    $email_escaped = mysqli_real_escape_string($conn, $email_input);

    // 2. ค้นหาข้อมูลบริษัทจากอีเมลที่ระบุ
    $sql_find_user = "SELECT * FROM company WHERE email = '$email_escaped'";
    $query_find_user = mysqli_query($conn, $sql_find_user);
    $user = mysqli_fetch_assoc($query_find_user);

    // 3. ตรวจสอบว่าพบผู้ใช้หรือไม่
    if (!$user) {
        $status = 'notfound';
    } else {
        // สร้างข้อความแจ้งเตือนถึงผู้ดูแลระบบทุกคน
        $company_name = $user['name'];
        $company_email = $user['email'];
        $notification_message = $company_name . ' (' . $company_email . ') ขอรีเซ็ตรหัสผ่าน';
        $escaped_message = mysqli_real_escape_string($conn, $notification_message);
        $link_url = 'admin/user_edit.php?q=' . urlencode($company_email);

        // ดึงรายชื่อผู้ดูแลระบบทั้งหมด (role_id = 1)
        $sql_admins = "SELECT * FROM company WHERE role_id = 1";
        $admins_query = mysqli_query($conn, $sql_admins);

        while ($admin = mysqli_fetch_assoc($admins_query)) {
            $admin_id = (int)$admin['id'];
            $sql_insert_noti = "INSERT INTO noti (recip_id, message, link, create_at) VALUES ($admin_id, '$escaped_message', '$link_url', NOW())";
            mysqli_query($conn, $sql_insert_noti);
        }

        $status = 'success';
    }
}

// กำหนดข้อความแจ้งเตือนและสีตามสถานะการทำงาน
$alert_message = '';
$alert_color = '';

if ($status == 'success') {
    $alert_message = 'ส่งคำขอเรียบร้อยแล้ว แจ้งผู้ดูแลระบบเพื่อรับการรีเซ็ตรหัสผ่าน';
    $alert_color = 'success';
} else if ($status == 'notfound') {
    $alert_message = 'ไม่พบอีเมลนี้ในระบบ กรุณาตรวจสอบอีเมลอีกครั้ง';
    $alert_color = 'danger';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - Ecolyn</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body { 
            background: #058d76; 
        }
        .card-white { 
            border-radius: 13px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-center align-items-center vh-100">
            <div class="card card-white shadow-sm p-4 col-md-5 col-lg-4">
                <h4 class="fw-bold mb-1">ลืมรหัสผ่าน</h4>
                <p class="text-muted small mb-3">ระบบจะส่งคำขอไปให้ผู้ดูแลระบบช่วยรีเซ็ตรหัสผ่าน</p>

                <?php if ($alert_message != '') { ?>
                    <div class="alert alert-<?php echo $alert_color; ?> py-2 small">
                        <?php echo $alert_message; ?>
                    </div>
                <?php } ?>

                <!-- ฟอร์มขอรีเซ็ตรหัสผ่าน -->
                <form method="post" action="">
                    <div class="mb-3">
                        <label class="form-label">อีเมลของคุณ</label>
                        <input type="email" name="email" class="form-control" placeholder="กรุณากรอกอีเมลที่ลงทะเบียนไว้" required autofocus value="<?php echo htmlspecialchars($email_input); ?>">
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2">ส่งคำขอ</button>
                    <div class="text-center mt-3 small">
                        <a href="login.php" class="text-success text-decoration-none">กลับไปหน้าเข้าสู่ระบบ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
