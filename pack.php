<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ถ้ายังไม่ล็อกอินให้กลับไปหน้า login
if (!isset($_SESSION['company_id'])) {
    header('location: login.php');
    exit();
}

$cid = (int)$_SESSION['company_id'];

// 1. ดึงข้อมูลแพ็กเกจปัจจุบันที่บริษัทนี้ใช้งานอยู่ (แถวล่าสุดที่อนุมัติแล้ว และยังไม่หมดอายุ)
$sql_current_member = "SELECT * FROM members WHERE company_id = $cid AND status_mem = 'Y' AND (end_mem IS NULL OR end_mem >= CURDATE()) ORDER BY id DESC LIMIT 1";
$query_current_member = mysqli_query($conn, $sql_current_member);
$current_member = mysqli_fetch_assoc($query_current_member);

if ($current_member) {
    $now_id = (int)$current_member['pack_id'];
} else {
    $now_id = 0;
}

// 2. ดึงรายการแพ็กเกจทั้งหมดเก็บไว้ในตัวแปร array ตาม id
$packs = array();
$sql_all_pack = "SELECT * FROM pack";
$query_all_pack = mysqli_query($conn, $sql_all_pack);

while ($p_item = mysqli_fetch_assoc($query_all_pack)) {
    $pack_item_id = (int)$p_item['id'];
    $packs[$pack_item_id] = $p_item;
}

// 3. เมื่อผู้ใช้กดเลือกสมัครหรือขอเปลี่ยนแพ็กเกจ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pack_id = (int)$_POST['pack_id'];

    if (isset($packs[$pack_id])) {
        $price = $packs[$pack_id]['price'];
    } else {
        $price = 0;
    }

    if ($current_member) {
        // กรณีมีแพ็กเกจอยู่แล้ว = ขอเปลี่ยนแพ็กเกจ (บันทึกแพ็กใหม่ลงแถวเดิม เพื่อรอการอนุมัติ)
        $member_id = (int)$current_member['id'];
        $sql_change_pack = "UPDATE members SET newpack_id = $pack_id, newpay = '$price', new_date = CURDATE() WHERE id = $member_id";
        mysqli_query($conn, $sql_change_pack);
        $action_text = 'ขอเปลี่ยนเป็นแพ็กเกจ';
    } else {
        // กรณียังไม่มีแพ็กเกจ = สมัครใหม่ (ลบคำขอเดิมที่ค้างอยู่ออก แล้วเพิ่มใหม่)
        $sql_clean_old = "DELETE FROM members WHERE company_id = $cid AND status_mem = 'N'";
        mysqli_query($conn, $sql_clean_old);

        $sql_insert_new = "INSERT INTO members (company_id, pack_id, status_mem) VALUES ($cid, $pack_id, 'N')";
        mysqli_query($conn, $sql_insert_new);
        $action_text = 'สมัครแพ็กเกจ';
    }

    // แจ้งเตือนไปยังผู้ดูแลระบบทุกคน
    $sql_company_info = "SELECT * FROM company WHERE id = $cid";
    $query_company_info = mysqli_query($conn, $sql_company_info);
    $my_company = mysqli_fetch_assoc($query_company_info);

    $chosen_pack_name = isset($packs[$pack_id]) ? $packs[$pack_id]['name'] : '';
    $noti_msg = "องค์กร '{$my_company['name']}' $action_text $chosen_pack_name";
    $escaped_noti_msg = mysqli_real_escape_string($conn, $noti_msg);

    $sql_admins = "SELECT * FROM company WHERE role_id = 1";
    $admins_query = mysqli_query($conn, $sql_admins);

    while ($admin = mysqli_fetch_assoc($admins_query)) {
        $admin_id = (int)$admin['id'];
        $sql_send_noti = "INSERT INTO noti (recip_id, message, type, link, create_at) VALUES ($admin_id, '$escaped_noti_msg', 'pack', 'admin/pack_review.php', NOW())";
        mysqli_query($conn, $sql_send_noti);
    }

    // เก็บข้อความสำเร็จในเซสชัน แล้วรีเฟรชหน้า
    $_SESSION['flash_msg_type'] = 'success';
    $_SESSION['flash_msg_text'] = 'ส่งคำขอเรียบร้อยแล้ว รอแอดมินอนุมัติ';
    header('location: pack.php');
    exit();
}

// ดึงข้อความแจ้งเตือนจากเซสชัน
$alert_type = '';
$alert_text = '';

if (isset($_SESSION['flash_msg_type'])) {
    $alert_type = $_SESSION['flash_msg_type'];
    $alert_text = $_SESSION['flash_msg_text'];
    unset($_SESSION['flash_msg_type']);
    unset($_SESSION['flash_msg_text']);
}

// 4. ค้นหาแพ็กเกจที่กำลังรออนุมัติ
$wait_id = 0;
if ($current_member && !empty($current_member['newpack_id'])) {
    $wait_id = (int)$current_member['newpack_id'];
} else {
    $sql_wait = "SELECT * FROM members WHERE company_id = $cid AND status_mem = 'N' LIMIT 1";
    $query_wait = mysqli_query($conn, $sql_wait);
    $wait_member = mysqli_fetch_assoc($query_wait);
    if ($wait_member) {
        $wait_id = (int)$wait_member['pack_id'];
    }
}

// 5. ดึงรายการแพ็กเกจทั้งหมดมาแสดง
$sql_pack_display = "SELECT * FROM pack ORDER BY id ASC";
$pack_result = mysqli_query($conn, $sql_pack_display);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เลือกแพ็กเกจ - Ecolyn</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">เลือกแพ็กเกจรักษ์โลก</h3>

        <?php if ($alert_text != '') { ?>
            <div class="alert alert-<?php echo $alert_type; ?> p-2 text-center mt-3">
                <?php echo $alert_text; ?>
            </div>
        <?php } ?>

        <div class="row">
            <?php 
            while ($p = mysqli_fetch_assoc($pack_result)) { 
                $this_pack_id = (int)$p['id'];
                $is_current_pack = ($this_pack_id == $now_id);
                $is_waiting_approval = ($this_pack_id == $wait_id);
            ?>
                <div class="col-md-6 mt-3">
                    <div class="card card-white shadow-sm p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($p['name']); ?></h5>
                            <p class="text-success fw-bold fs-5 mb-0"><?php echo number_format($p['price']); ?> บาท</p>
                        </div>
                        <div class="mt-3 border-top pt-2">
                            <p class="text-muted small"><?php echo nl2br(htmlspecialchars($p['detail'])); ?></p>
                        </div>
                        <div class="mt-auto pt-4">
                            <?php if ($is_current_pack) { ?>
                                <button type="button" class="btn btn-secondary w-100" disabled>คุณใช้แพ็กเกจนี้อยู่</button>
                            <?php } else if ($is_waiting_approval) { ?>
                                <button type="button" class="btn btn-warning w-100" disabled>รอแอดมินอนุมัติ</button>
                            <?php } else { ?>
                                <form method="post" action="">
                                    <input type="hidden" name="pack_id" value="<?php echo $this_pack_id; ?>">
                                    <button type="submit" class="btn btn-success w-100">เลือกแพ็กเกจ</button>
                                </form>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include 'footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
