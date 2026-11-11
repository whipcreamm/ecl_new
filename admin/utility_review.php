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

// เมื่อผู้ดูแลระบบกดอนุมัติหรือไม่อนุมัติหลักฐานค่าน้ำ-ไฟ
if (isset($_POST['review_id'])) {
    $id = (int)$_POST['review_id'];
    $is_approve = (isset($_POST['action']) && $_POST['action'] == 'approve');
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $escaped_reason = mysqli_real_escape_string($conn, $reason);

    // ค้นหารายการที่รอการตรวจสอบ (review_status = 'N')
    $sql_find_log = "SELECT * FROM utility_logs WHERE id = $id AND review_status = 'N'";
    $query_find_log = mysqli_query($conn, $sql_find_log);
    $row = mysqli_fetch_assoc($query_find_log);

    if ($row) {
        if ($is_approve) {
            $new_status = 'Y';
        } else {
            $new_status = 'R';
        }

        // อัปเดตสถานะการตรวจและบันทึกเหตุผล
        $sql_update_log = "UPDATE utility_logs SET review_status = '$new_status', review_note = '$escaped_reason' WHERE id = $id";
        mysqli_query($conn, $sql_update_log);

        // ส่งข้อความแจ้งเตือนไปยังบริษัทสมาชิก
        $target_cid = (int)$row['company_id'];
        $type_name_thai = ($row['type'] == 'elec') ? 'ค่าไฟ' : 'ค่าน้ำ';
        $month_display = date('m/Y', strtotime($row['record_date']));

        if ($is_approve) {
            $noti_text = "หลักฐาน$type_name_thai ประจำเดือน $month_display ได้รับการอนุมัติแล้ว";
        } else {
            $noti_text = "หลักฐาน$type_name_thai ประจำเดือน $month_display ไม่ผ่านการตรวจสอบ เหตุผล: $reason";
        }

        $escaped_noti = mysqli_real_escape_string($conn, $noti_text);
        $sql_send_noti = "INSERT INTO noti (recip_id, message, type, link, create_at) VALUES ($target_cid, '$escaped_noti', 'utility', 'utility.php', NOW())";
        mysqli_query($conn, $sql_send_noti);

        if ($is_approve) {
            $_SESSION['flash_type'] = 'success';
            $_SESSION['flash_text'] = 'อนุมัติหลักฐานเรียบร้อยแล้ว';
        } else {
            $_SESSION['flash_type'] = 'warning';
            $_SESSION['flash_text'] = 'ไม่อนุมัติหลักฐาน และแจ้งเตือนผู้ใช้เรียบร้อยแล้ว';
        }
    } else {
        $_SESSION['flash_type'] = 'warning';
        $_SESSION['flash_text'] = 'ไม่พบรายการ หรือรายการนี้ได้รับการตรวจไปเรียบร้อยแล้ว';
    }

    // ส่งผู้ใช้กลับไปยังหน้าเดิมพร้อมคงค่าตัวกรองประเภทไว้
    $filter_type = isset($_POST['ft']) ? $_POST['ft'] : '';
    if ($filter_type != '') {
        header('location: utility_review.php?type=' . urlencode($filter_type));
    } else {
        header('location: utility_review.php');
    }
    exit();
}

// ดึงข้อความแจ้งเตือนจากเซสชัน
$alert_type = '';
$alert_text = '';

if (isset($_SESSION['flash_type'])) {
    $alert_type = $_SESSION['flash_type'];
    $alert_text = $_SESSION['flash_text'];
    unset($_SESSION['flash_type']);
    unset($_SESSION['flash_text']);
}

// ตัวกรองหมวดหมู่การใช้งาน: elec, water หรือว่าง (ทั้งหมด)
$filter = isset($_GET['type']) ? $_GET['type'] : '';
if ($filter != 'elec' && $filter != 'water') {
    $filter = '';
}

$where_condition = '';
if ($filter != '') {
    $where_condition = "WHERE type = '$filter'";
}

// ดึงข้อมูลบริษัททั้งหมดเก็บไว้ใน array
$companies_map = array();
$sql_c = "SELECT * FROM company";
$query_c = mysqli_query($conn, $sql_c);
while ($comp = mysqli_fetch_assoc($query_c)) {
    $comp_id = (int)$comp['id'];
    $companies_map[$comp_id] = $comp;
}

// ดึงรายการบันทึกค่าน้ำ-ไฟทั้งหมด นำรายการที่รอตรวจ (review_status = 'N') ขึ้นก่อน
$sql_logs = "SELECT * FROM utility_logs $where_condition ORDER BY (review_status = 'N') DESC, record_date DESC, id DESC";
$log_result = mysqli_query($conn, $sql_logs);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจหลักฐานบันทึกค่าน้ำ-ค่าไฟ - Ecolyn Admin</title>
    <?php include '../navbar.php'; ?>
    <style>
        .bg-orange {
            background-color: #f87d09;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid px-4">
        <h3 class="fw-bold mt-3 text-center">ตรวจหลักฐานบันทึกค่าน้ำ-ค่าไฟ</h3>

        <!-- ปุ่มตัวกรองหมวดหมู่ -->
        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="utility_review.php" class="btn <?php if ($filter == '') { echo 'btn-dark'; } else { echo 'btn-outline-dark'; } ?>">ทั้งหมด</a>
            <a href="utility_review.php?type=elec" class="btn <?php if ($filter == 'elec') { echo 'btn-warning text-dark'; } else { echo 'btn-outline-warning text-dark'; } ?>">⚡ ไฟฟ้า</a>
            <a href="utility_review.php?type=water" class="btn <?php if ($filter == 'water') { echo 'btn-primary'; } else { echo 'btn-outline-primary'; } ?>">💧 น้ำ</a>
        </div>

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
                            <th class="text-start">ชื่อบริษัท</th>
                            <th>หมวด</th>
                            <th>ประจำเดือน</th>
                            <th>มิเตอร์ ต้น-ปลาย</th>
                            <th>ปริมาณที่ใช้</th>
                            <th>ปริมาณเดือนก่อน</th>
                            <th>หลักฐานสลิป</th>
                            <th>สถานะการตรวจ</th>
                            <th style="min-width: 220px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($l = mysqli_fetch_assoc($log_result)) { 
                        $log_cid = (int)$l['company_id'];
                        $co = isset($companies_map[$log_cid]) ? $companies_map[$log_cid] : null;

                        $is_electricity = ($l['type'] == 'elec');
                        $review_st = $l['review_status'];
                    ?>
                        <tr>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($co ? $co['name'] : '-'); ?></td>
                            <td>
                                <span class="badge <?php if ($is_electricity) { echo 'bg-orange'; } else { echo 'bg-primary'; } ?>">
                                    <?php echo $is_electricity ? 'ไฟฟ้า' : 'น้ำ'; ?>
                                </span>
                            </td>
                            <td><?php echo date('m/Y', strtotime($l['record_date'])); ?></td>
                            <td><?php echo number_format($l['meter_start'], 2); ?> - <?php echo number_format($l['meter_read'], 2); ?></td>
                            <td class="text-success fw-bold"><?php echo number_format($l['amount'], 2); ?></td>
                            <td><?php echo number_format($l['prev_amount'], 2); ?></td>
                            <td class="text-nowrap">
                                <?php if (!empty($l['evidence_url'])) { ?>
                                    <a href="../uploads/<?php echo htmlspecialchars($l['evidence_url']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">เดือนนี้</a>
                                <?php } ?>
                                <?php if (!empty($l['evidence_prev_url'])) { ?>
                                    <a href="../uploads/<?php echo htmlspecialchars($l['evidence_prev_url']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">เดือนก่อน</a>
                                <?php } ?>
                                <?php if (empty($l['evidence_url']) && empty($l['evidence_prev_url'])) { ?>
                                    <span class="text-muted small">ไม่มีรูปภาพ</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php 
                                if ($review_st == 'Y') {
                                    echo '<span class="badge bg-success">อนุมัติแล้ว</span>';
                                } else if ($review_st == 'R') {
                                    echo '<span class="badge bg-danger">ไม่อนุมัติ</span>';
                                } else {
                                    echo '<span class="badge bg-warning text-dark">รอตรวจ</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($review_st == 'Y') { ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled>อนุมัติแล้ว</button>
                                <?php } else if ($review_st == 'R') { ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled>ไม่อนุมัติ: <?php echo htmlspecialchars($l['review_note']); ?></button>
                                <?php } else { ?>
                                    <form method="post" action="utility_review.php">
                                        <input type="hidden" name="review_id" value="<?php echo (int)$l['id']; ?>">
                                        <input type="hidden" name="ft" value="<?php echo htmlspecialchars($filter); ?>">
                                        <input type="text" name="reason" class="form-control form-control-sm mb-1" placeholder="ระบุเหตุผล (ถ้าไม่อนุมัติ)">
                                        <div class="d-flex gap-1">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success w-100" onclick="return confirm('ยืนยันอนุมัติหลักฐานนี้ใช่หรือไม่?')">อนุมัติ</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger w-100" onclick="return confirm('ยืนยันไม่อนุมัติหลักฐานนี้ใช่หรือไม่?')">ไม่อนุมัติ</button>
                                        </div>
                                    </form>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (mysqli_num_rows($log_result) == 0) { ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">ไม่พบรายการบันทึกค่าน้ำ-ค่าไฟ</td>
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
