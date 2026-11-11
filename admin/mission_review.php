<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include '../config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ถ้ายังไม่ล็อกอินให้กลับไปหน้า login
if (!isset($_SESSION['company_id'])) {
    header('location: ../login.php');
    exit();
}

$cid = (int)$_SESSION['company_id'];

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin: role_id = 1)
$sql_check_admin = "SELECT * FROM company WHERE id = $cid";
$query_admin = mysqli_query($conn, $sql_check_admin);
$me = mysqli_fetch_assoc($query_admin);

if (!$me || $me['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

// เมื่อผู้ดูแลระบบกดอนุมัติหรือไม่อนุมัติหลักฐาน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $status_redirect = 'error';

    // ค้นหารายการการส่งหลักฐานภารกิจที่อยู่ในสถานะรอตรวจ (pending)
    $sql_find_parti = "SELECT * FROM mission_parti WHERE id = $id AND status = 'pending'";
    $query_find_parti = mysqli_query($conn, $sql_find_parti);
    $row = mysqli_fetch_assoc($query_find_parti);

    if ($row) {
        $mission_id = (int)$row['mission_id'];
        $sql_find_m = "SELECT * FROM mission WHERE id = $mission_id";
        $query_find_m = mysqli_query($conn, $sql_find_m);
        $mission = mysqli_fetch_assoc($query_find_m);

        $target_cid = (int)$row['company_id'];
        $point_reward = (int)$mission['point_reward'];
        $mission_title = $mission['title'];
        $escaped_title = mysqli_real_escape_string($conn, $mission_title);
        $noti_message = '';

        if ($action == 'approve') {
            // อัปเดตสถานะเป็นสำเร็จ (complete)
            $sql_approve = "UPDATE mission_parti SET status = 'complete' WHERE id = $id";
            mysqli_query($conn, $sql_approve);

            // บันทึกแต้มรางวัลเข้าบัญชีขององค์กรสมาชิก
            $point_reason = "สำเร็จภารกิจ: $escaped_title";
            $sql_add_points = "INSERT INTO eco_point_ledger (company_id, point, reason) VALUES ($target_cid, $point_reward, '$point_reason')";
            mysqli_query($conn, $sql_add_points);

            $noti_message = "ยินดีด้วย! ภารกิจ '$mission_title' ได้รับการอนุมัติแล้ว (+$point_reward แต้ม)";
            $status_redirect = 'approve';

        } else if ($action == 'reject') {
            if ($reason == '') {
                $status_redirect = 'reason'; // ไม่ได้ระบุเหตุผล
            } else {
                // อัปเดตสถานะเป็นไม่ผ่าน (reject)
                $sql_reject = "UPDATE mission_parti SET status = 'reject' WHERE id = $id";
                mysqli_query($conn, $sql_reject);

                $noti_message = "หลักฐานภารกิจ '$mission_title' ไม่ผ่านการอนุมัติ เหตุผล: $reason กรุณาตรวจสอบและส่งใหม่";
                $status_redirect = 'reject';
            }
        }

        // ส่งการแจ้งเตือนไปยังสมาชิก
        if ($noti_message != '') {
            $escaped_noti = mysqli_real_escape_string($conn, $noti_message);
            $sql_send_noti = "INSERT INTO noti (recip_id, message, type, link, create_at) VALUES ($target_cid, '$escaped_noti', 'mission', 'mission.php', NOW())";
            mysqli_query($conn, $sql_send_noti);
        }
    }

    header('location: mission_review.php?status=' . $status_redirect);
    exit();
}

// จัดการข้อความแจ้งเตือนตาม status ใน URL
$alert_type = '';
$alert_text = '';

if (isset($_GET['status'])) {
    $cur_status = $_GET['status'];
    if ($cur_status == 'approve') {
        $alert_type = 'success';
        $alert_text = 'อนุมัติภารกิจและมอบแต้มให้สมาชิกเรียบร้อยแล้ว';
    } else if ($cur_status == 'reject') {
        $alert_type = 'warning';
        $alert_text = 'ไม่อนุมัติหลักฐานภารกิจนี้ และแจ้งเหตุผลให้สมาชิกทราบแล้ว';
    } else if ($cur_status == 'reason') {
        $alert_type = 'warning';
        $alert_text = 'กรุณาระบุเหตุผลที่ไม่อนุมัติหลักฐานก่อนดำเนินการ';
    } else if ($cur_status == 'error') {
        $alert_type = 'danger';
        $alert_text = 'ไม่พบรายการหลักฐาน หรือรายการนี้ถูกจัดการไปเรียบร้อยแล้ว';
    }
}

// ดึงข้อมูลภารกิจและบริษัททั้งหมดเก็บไว้ใน array
$missions_map = array();
$sql_m = "SELECT * FROM mission";
$query_m = mysqli_query($conn, $sql_m);
while ($m_item = mysqli_fetch_assoc($query_m)) {
    $m_id = (int)$m_item['id'];
    $missions_map[$m_id] = $m_item;
}

$companies_map = array();
$sql_c = "SELECT * FROM company";
$query_c = mysqli_query($conn, $sql_c);
while ($c_item = mysqli_fetch_assoc($query_c)) {
    $c_id = (int)$c_item['id'];
    $companies_map[$c_id] = $c_item;
}

// ดึงรายการหลักฐานภารกิจที่รอการตรวจสอบ (status = 'pending')
$sql_pending_missions = "SELECT * FROM mission_parti WHERE status = 'pending' ORDER BY submit_at DESC, id DESC";
$mission_result = mysqli_query($conn, $sql_pending_missions);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจหลักฐานภารกิจ - Ecolyn Admin</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ตรวจหลักฐานภารกิจ</h3>

        <?php if ($alert_text != '') { ?>
            <div class="alert alert-<?php echo $alert_type; ?> p-2 text-center mt-3">
                <?php echo $alert_text; ?>
            </div>
        <?php } ?>

        <div class="card shadow-sm p-3 mt-3">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-start">ภารกิจ</th>
                            <th>แต้มรางวัล</th>
                            <th class="text-start">ผู้ส่งหลักฐาน</th>
                            <th>ส่งเมื่อ</th>
                            <th>คำอธิบายเพิ่มเติม</th>
                            <th>รูปภาพหลักฐาน</th>
                            <th style="min-width: 220px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($m = mysqli_fetch_assoc($mission_result)) { 
                        $mid = (int)$m['mission_id'];
                        $cid_parti = (int)$m['company_id'];

                        $mi = isset($missions_map[$mid]) ? $missions_map[$mid] : null;
                        $co = isset($companies_map[$cid_parti]) ? $companies_map[$cid_parti] : null;

                        $submit_time = !empty($m['submit_at']) ? date('d/m/Y H:i', strtotime($m['submit_at'])) : '-';
                        $evidence_pic = $m['evidence_url'];
                    ?>
                        <tr>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($mi ? $mi['title'] : '-'); ?></td>
                            <td class="text-success fw-bold">+<?php echo number_format($mi ? $mi['point_reward'] : 0); ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($co ? $co['name'] : '-'); ?></td>
                            <td><?php echo $submit_time; ?></td>
                            <td class="text-start"><small><?php echo !empty($m['note']) ? htmlspecialchars($m['note']) : '-'; ?></small></td>
                            <td>
                                <?php if ($evidence_pic != '') { ?>
                                    <a href="../uploads/<?php echo htmlspecialchars($evidence_pic); ?>" target="_blank">
                                        <img src="../uploads/<?php echo htmlspecialchars($evidence_pic); ?>" class="border rounded" style="height: 60px; object-fit: cover;">
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted small">ไม่มีรูปภาพ</span>
                                <?php } ?>
                            </td>
                            <td>
                                <form method="post" action="mission_review.php">
                                    <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                    <input type="text" name="reason" class="form-control form-control-sm mb-1" placeholder="เหตุผล (ถ้าไม่ผ่าน)">
                                    <div class="d-flex gap-1">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success w-100">อนุมัติ</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger w-100" onclick="return confirm('ยืนยันไม่อนุมัติหลักฐานนี้?')">ไม่ผ่าน</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (mysqli_num_rows($mission_result) == 0) { ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">ไม่มีรายการหลักฐานภารกิจที่รอตรวจสอบในขณะนี้</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include '../footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
