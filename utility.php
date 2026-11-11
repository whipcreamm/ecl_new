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

// 1. ตรวจสอบว่ามีแพ็กเกจที่ใช้งานได้อยู่หรือไม่
$sql_check_pack = "SELECT * FROM members WHERE company_id = $cid AND status_mem = 'Y' AND (end_mem IS NULL OR end_mem >= CURDATE())";
$pack_query = mysqli_query($conn, $sql_check_pack);
$has_pack = mysqli_num_rows($pack_query);

if ($has_pack == 0) {
    header('location: pack.php');
    exit();
}

// 2. ดึงค่าตั้งค่าระบบจากตาราง eco_setting
$settings = array();
$sql_settings = "SELECT * FROM eco_setting";
$query_settings = mysqli_query($conn, $sql_settings);

while ($s_row = mysqli_fetch_assoc($query_settings)) {
    $settings[$s_row['setting_key']] = $s_row['setting_value'];
}

if (isset($settings['point_per_kg'])) {
    $point_per_kg = (float)$settings['point_per_kg'];
} else {
    $point_per_kg = 10;
}

if (isset($settings['factor_elec'])) {
    $factor_elec = (float)$settings['factor_elec'];
} else {
    $factor_elec = 0.4999;
}

if (isset($settings['factor_water'])) {
    $factor_water = (float)$settings['factor_water'];
} else {
    $factor_water = 0.7;
}

// กำหนดรายละเอียดของแต่ละประเภท (ชื่อ, หน่วย, factor, ราคาต่อหน่วยตั้งต้น, คำเรียกภาษาไทย)
$types = array(
    'elec'  => array('name' => '⚡ ไฟฟ้า', 'unit' => 'kWh',   'factor' => $factor_elec,  'default_price' => 4.5, 'title' => 'ค่าไฟ'),
    'water' => array('name' => '💧 น้ำ',   'unit' => 'ลบ.ม.', 'factor' => $factor_water, 'default_price' => 18,  'title' => 'ค่าน้ำ')
);

// 3. ดึงบันทึกการใช้พลังงานของบริษัทเราทั้งหมด
$all_logs = array();
$rejected_logs = array();
$rejected_months = array();

$sql_my_logs = "SELECT * FROM utility_logs WHERE company_id = $cid ORDER BY record_date ASC, id ASC";
$query_my_logs = mysqli_query($conn, $sql_my_logs);

while ($row = mysqli_fetch_assoc($query_my_logs)) {
    $all_logs[] = $row;
    if ($row['review_status'] == 'R') {
        $rejected_logs[] = $row;
        $row_type = $row['type'];
        $row_month = date('Y-m', strtotime($row['record_date']));
        $rejected_months[$row_type][$row_month] = 1;
    }
}

// กำหนดเดือนที่ให้กรอกใหม่สำหรับแต่ละหมวด
$redo_month = array();
foreach ($types as $t_key => $t_info) {
    $redo_month[$t_key] = date('Y-m');
}
$reversed_rejected = array_reverse($rejected_logs);
foreach ($reversed_rejected as $rej_item) {
    $rej_type = $rej_item['type'];
    $redo_month[$rej_type] = date('Y-m', strtotime($rej_item['record_date']));
}

// ประมวลผลข้อมูลที่ใช้ได้ (ไม่รวมเดือนที่ถูกปฏิเสธ) เพื่อหาค่าล่าสุด และสถิติย้อนหลัง 1 ปี
$has_previous_record = array();
$last_record_by_type = array();
$total_previous_carbon = 0;
$total_current_carbon = 0;
$year_recorded_months = array();
$one_year_ago_date = date('Y-m-d', strtotime('-12 month'));

foreach ($all_logs as $log_item) {
    $log_type = $log_item['type'];
    $log_month = date('Y-m', strtotime($log_item['record_date']));

    // ข้ามรายการที่อยู่ในเดือนที่ถูกปฏิเสธ
    if (isset($rejected_months[$log_type][$log_month])) {
        continue;
    }

    $has_previous_record[$log_type] = true;
    $last_record_by_type[$log_type] = $log_item;

    if ($log_item['record_date'] > $one_year_ago_date) {
        $type_factor = $types[$log_type]['factor'];
        $current_amount = (float)$log_item['amount'];
        $prev_amount = (float)$log_item['prev_amount'];

        $total_current_carbon = $total_current_carbon + ($current_amount * $type_factor);
        $total_previous_carbon = $total_previous_carbon + ($prev_amount * $type_factor);
        $year_recorded_months[$log_month] = 1;
    }
}

// กำหนดค่าตั้งต้นในฟอร์มของแต่ละประเภท
$form_defaults = array();
foreach ($types as $t_key => $t_info) {
    if (isset($last_record_by_type[$t_key])) {
        $last_rec = $last_record_by_type[$t_key];
        $form_defaults[$t_key] = array(
            'start' => $last_rec['meter_read'],
            'prev'  => $last_rec['amount'],
            'price' => $last_rec['unit_price']
        );
    } else {
        $form_defaults[$t_key] = array(
            'start' => 0,
            'prev'  => 0,
            'price' => $t_info['default_price']
        );
    }
}

// 4. ตรวจสอบเงื่อนไขการขอรับใบรับรองลดคาร์บอน (ลดได้ >= 10% และบันทึก >= 10 เดือน)
if ($total_previous_carbon > 0) {
    $reduction_percent = (($total_previous_carbon - $total_current_carbon) / $total_previous_carbon) * 100;
} else {
    $reduction_percent = 0;
}
$recorded_months_count = count($year_recorded_months);
$can_request_certificate = ($reduction_percent >= 10 && $recorded_months_count >= 10);

// ตรวจสอบว่าเคยส่งคำขอใบรับรองไปแล้วในช่วง 30 วันที่ผ่านมาหรือไม่
$sql_company_info = "SELECT * FROM company WHERE id = $cid";
$company_query = mysqli_query($conn, $sql_company_info);
$company_info = mysqli_fetch_assoc($company_query);
$company_name = $company_info ? $company_info['name'] : '';

$already_requested_cert = false;
$thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 day'));
$sql_check_cert_noti = "SELECT * FROM noti WHERE type = 'cert' AND create_at >= '$thirty_days_ago'";
$query_cert_noti = mysqli_query($conn, $sql_check_cert_noti);

while ($noti_row = mysqli_fetch_assoc($query_cert_noti)) {
    if (strpos($noti_row['message'], $company_name) !== false) {
        $already_requested_cert = true;
    }
}

// 5. จัดการเมื่อผู้ใช้กดส่งฟอร์ม (ขอรับใบรับรอง หรือ บันทึกข้อมูลค่าน้ำ-ค่าไฟ)
$error_messages = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // กรณีที่ 1: กดปุ่มขอรับใบรับรอง
    if (isset($_POST['request_cert'])) {
        if ($can_request_certificate) {
            $sql_update_cert = "UPDATE members SET cert_status = 'N', cert_date_req = CURDATE() WHERE company_id = $cid";
            mysqli_query($conn, $sql_update_cert);

            $percent_display = number_format($reduction_percent, 1);
            $cert_msg = "องค์กร '$company_name' ยื่นคำขอรับใบรับรอง (ลดคาร์บอนได้ $percent_display% บันทึกครบ $recorded_months_count เดือน)";
            $escaped_cert_msg = mysqli_real_escape_string($conn, $cert_msg);

            $sql_admins = "SELECT * FROM company WHERE role_id = 1";
            $admins_query = mysqli_query($conn, $sql_admins);
            while ($admin = mysqli_fetch_assoc($admins_query)) {
                $admin_id = (int)$admin['id'];
                $sql_insert_noti = "INSERT INTO noti (recip_id, message, type, link, create_at) VALUES ($admin_id, '$escaped_cert_msg', 'cert', 'admin/certificate_review.php', NOW())";
                mysqli_query($conn, $sql_insert_noti);
            }

            $_SESSION['flash_type'] = 'success';
            $_SESSION['flash_text'] = 'ส่งคำขอรับใบรับรองเรียบร้อยแล้ว';
        } else {
            $_SESSION['flash_type'] = 'danger';
            $_SESSION['flash_text'] = 'ยังไม่ถึงเกณฑ์ที่กำหนด (ลดคาร์บอน ' . number_format($reduction_percent, 1) . '% จาก 10% | บันทึก ' . $recorded_months_count . '/10 เดือน)';
        }
        header('location: utility.php');
        exit();
    }

    // กรณีที่ 2: บันทึกข้อมูลค่าน้ำ หรือ ค่าไฟ
    $rec_type = $_POST['rec_type'];
    if (!isset($types[$rec_type])) {
        exit();
    }

    $type_info = $types[$rec_type];
    $record_month = $_POST['record_month'];
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    $month_display = date('m/Y', strtotime($record_month . '-01'));
    $has_prev = isset($has_previous_record[$rec_type]);

    // ตรวจสอบบันทึกซ้ำ หรือบันทึกเดือนที่ถูกปฏิเสธ
    $is_rejected_month = isset($rejected_months[$rec_type][$record_month]);
    $is_duplicate = false;
    foreach ($all_logs as $existing_log) {
        if ($existing_log['type'] == $rec_type) {
            $existing_month = date('Y-m', strtotime($existing_log['record_date']));
            if ($existing_month == $record_month) {
                $is_duplicate = true;
            }
        }
    }

    if ($record_month > date('Y-m')) {
        $error_messages[] = 'ไม่สามารถบันทึกข้อมูลของเดือนในอนาคตได้';
    } else if (!$is_rejected_month && $is_duplicate) {
        $error_messages[] = 'คุณได้บันทึกข้อมูล' . $type_info['title'] . 'ประจำเดือน ' . $month_display . ' ไปเรียบร้อยแล้ว';
    }

    // รับค่าตัวเลขมิเตอร์
    if ($has_prev) {
        $meter_start = (float)$form_defaults[$rec_type]['start'];
        $prev_amount = (float)$form_defaults[$rec_type]['prev'];
    } else {
        $meter_start = (float)$_POST['start_' . $rec_type];
        $prev_amount = (float)$_POST['prev_' . $rec_type];
    }

    $meter_read = (float)$_POST['end_' . $rec_type];
    $unit_price = (float)$_POST['price_' . $rec_type];

    if ($meter_read < $meter_start) {
        $error_messages[] = 'เลขมิเตอร์ปลายเดือนต้องไม่น้อยกว่าเลขมิเตอร์ต้นเดือน';
    }

    $amount_used = $meter_read - $meter_start;
    $cost = $amount_used * $unit_price;
    $save_unit = ($prev_amount - $amount_used) * $unit_price;
    $co2e_save = ($prev_amount - $amount_used) * $type_info['factor'];

    if (count($error_messages) == 0) {
        $reason_text = "ลดการใช้{$type_info['title']}ประจำเดือน $month_display";
        $escaped_reason = mysqli_real_escape_string($conn, $reason_text);

        // กรณีเป็นเดือนที่ถูกปฏิเสธ: ลบข้อมูลเก่าและหักลบแต้มเดิมที่เคยให้
        if ($is_rejected_month) {
            $old_points = 0;
            $sql_find_old_pts = "SELECT * FROM eco_point_ledger WHERE company_id = $cid";
            $query_old_pts = mysqli_query($conn, $sql_find_old_pts);
            while ($p_row = mysqli_fetch_assoc($query_old_pts)) {
                if ($p_row['reason'] == $reason_text) {
                    $old_points = $old_points + (int)$p_row['point'];
                }
            }

            if ($old_points != 0) {
                $negative_pts = -$old_points;
                $sql_cancel_pts = "INSERT INTO eco_point_ledger (company_id, point, reason) VALUES ($cid, $negative_pts, '$escaped_reason')";
                mysqli_query($conn, $sql_cancel_pts);
            }

            foreach ($all_logs as $old_log) {
                if ($old_log['type'] == $rec_type) {
                    $old_log_month = date('Y-m', strtotime($old_log['record_date']));
                    if ($old_log_month == $record_month) {
                        $old_log_id = (int)$old_log['id'];
                        mysqli_query($conn, "DELETE FROM utility_logs WHERE id = $old_log_id");
                    }
                }
            }
        }

        // จัดการอัปโหลดรูปภาพสลิปหลักฐานประจำเดือนนี้
        $uploaded_slip = '';
        if (isset($_FILES['evidence_' . $rec_type]) && $_FILES['evidence_' . $rec_type]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['evidence_' . $rec_type]['name'], PATHINFO_EXTENSION));
            $uploaded_slip = uniqid('ev_') . '.' . $ext;
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            move_uploaded_file($_FILES['evidence_' . $rec_type]['tmp_name'], 'uploads/' . $uploaded_slip);
        }

        // จัดการอัปโหลดรูปภาพสลิปหลักฐานของเดือนก่อน (เฉพาะครั้งแรกที่ยังไม่มีข้อมูลเดิม)
        $uploaded_prev_slip = '';
        if (!$has_prev && isset($_FILES['evidence_prev_' . $rec_type]) && $_FILES['evidence_prev_' . $rec_type]['error'] === UPLOAD_ERR_OK) {
            $ext_prev = strtolower(pathinfo($_FILES['evidence_prev_' . $rec_type]['name'], PATHINFO_EXTENSION));
            $uploaded_prev_slip = uniqid('ev_') . '.' . $ext_prev;
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            move_uploaded_file($_FILES['evidence_prev_' . $rec_type]['tmp_name'], 'uploads/' . $uploaded_prev_slip);
        }

        $escaped_note = mysqli_real_escape_string($conn, $note);
        $full_record_date = $record_month . '-01';

        // บันทึกลงในตาราง utility_logs
        $sql_insert_log = "INSERT INTO utility_logs (company_id, type, meter_start, meter_read, unit_price, prev_amount, amount, cost, save_unit, co2e_save, evidence_url, evidence_prev_url, note, record_date) VALUES ($cid, '$rec_type', '$meter_start', '$meter_read', '$unit_price', '$prev_amount', '$amount_used', '$cost', '$save_unit', '$co2e_save', '$uploaded_slip', '$uploaded_prev_slip', '$escaped_note', '$full_record_date')";
        mysqli_query($conn, $sql_insert_log);

        // คำนวณแต้มสะสมที่ได้รับ
        $diff_carbon = $co2e_save;
        if ($prev_amount > 0) {
            $diff_percent = ($diff_carbon / ($prev_amount * $type_info['factor'])) * 100;
        } else {
            $diff_percent = 0;
        }

        if ($diff_carbon > 0) {
            $earned_points = round($diff_carbon * $point_per_kg);
        } else {
            $earned_points = 0;
        }

        if ($earned_points > 0) {
            $sql_award_points = "INSERT INTO eco_point_ledger (company_id, point, reason) VALUES ($cid, $earned_points, '$escaped_reason')";
            mysqli_query($conn, $sql_award_points);
        }

        $success_msg = "บันทึก{$type_info['title']} $month_display สำเร็จ! ประหยัด " . number_format($save_unit, 2) . " บาท | ลดคาร์บอนได้ " . number_format($diff_carbon, 2) . " kg (" . number_format($diff_percent, 1) . "%)";
        if ($earned_points > 0) {
            $success_msg = $success_msg . " (ได้รับ +$earned_points แต้ม)";
        }

        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_text'] = $success_msg;
        header('location: utility.php');
        exit();
    }
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

// ดึงรายการประวัติการบันทึก 20 รายการล่าสุด
$sql_recent_logs = "SELECT * FROM utility_logs WHERE company_id = $cid ORDER BY record_date DESC, id DESC LIMIT 20";
$recent_logs_query = mysqli_query($conn, $sql_recent_logs);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกค่าน้ำ-ค่าไฟ - Ecolyn</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">บันทึกการใช้น้ำ-ไฟฟ้า (รายเดือน)</h4>
            <div>
                <?php foreach ($types as $t_key => $t_info) { ?>
                    <button type="button" class="btn <?php if ($t_key == 'elec') { echo 'btn-warning text-dark'; } else { echo 'btn-primary'; } ?> ms-2" data-bs-toggle="modal" data-bs-target="#addModal_<?php echo $t_key; ?>">
                        + บันทึก<?php echo $t_info['title']; ?>
                    </button>
                <?php } ?>
            </div>
        </div>

        <?php if ($alert_text != '') { ?>
            <div class="alert alert-<?php echo $alert_type; ?> p-3 mb-3">
                <?php echo $alert_text; ?>
            </div>
        <?php } ?>

        <?php foreach ($error_messages as $err) { ?>
            <div class="alert alert-danger py-2 mb-2">
                <?php echo $err; ?>
            </div>
        <?php } ?>

        <?php foreach ($rejected_logs as $rej) { ?>
            <div class="alert alert-danger py-2 mb-2">
                หลักฐาน<?php echo $types[$rej['type']]['title']; ?> ประจำเดือน <?php echo date('m/Y', strtotime($rej['record_date'])); ?> ไม่ผ่านการตรวจสอบ: 
                <strong><?php echo htmlspecialchars($rej['review_note']); ?></strong> — กรุณากรอกข้อมูลเดือนนี้ใหม่อีกครั้ง
            </div>
        <?php } ?>

        <!-- กล่องสถานะการขอรับใบรับรอง -->
        <div class="card shadow-sm mb-3 <?php if ($can_request_certificate) { echo 'bg-warning-subtle'; } ?>">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-1">ขอรับใบรับรองลดคาร์บอน (Certificate)</h6>
                    <small class="text-muted d-block">เงื่อนไข: ลดคาร์บอนได้ 10% ขึ้นไป และบันทึกข้อมูลครบ 10 เดือน (ภายในรอบ 1 ปี)</small>
                    <small>
                        ลดได้: <b class="<?php if ($reduction_percent >= 10) { echo 'text-success'; } else { echo 'text-danger'; } ?>"><?php echo number_format($reduction_percent, 1); ?>%</b> · 
                        บันทึกแล้ว: <b class="<?php if ($recorded_months_count >= 10) { echo 'text-success'; } else { echo 'text-danger'; } ?>"><?php echo $recorded_months_count; ?>/10 เดือน</b>
                        <?php if ($already_requested_cert) { ?>
                            <span class="badge bg-info text-dark ms-2">ส่งคำขอแล้ว</span>
                        <?php } ?>
                    </small>
                </div>
                <form method="post" action="utility.php" onsubmit="return confirm('ยืนยันการขอรับใบรับรองลดคาร์บอน?')">
                    <button type="submit" name="request_cert" value="1" class="btn <?php if ($can_request_certificate) { echo 'btn-warning text-dark fw-bold'; } else { echo 'btn-secondary'; } ?>" <?php if (!$can_request_certificate) { echo 'disabled'; } ?>>
                        ขอรับใบรับรอง
                    </button>
                </form>
            </div>
        </div>

        <!-- ตารางประวัติบันทึกข้อมูลล่าสุด -->
        <div class="card shadow-sm p-3">
            <h6 class="fw-bold mb-3">ประวัติการบันทึกข้อมูลล่าสุด</h6>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ประจำเดือน</th>
                            <th>หมวด</th>
                            <th>มิเตอร์ ต้น-ปลาย</th>
                            <th>ปริมาณที่ใช้</th>
                            <th>ค่าใช้จ่าย (บาท)</th>
                            <th>ประหยัด (บาท)</th>
                            <th>CO₂ ลดได้ (kg)</th>
                            <th>หลักฐานสลิป</th>
                            <th>สถานะการตรวจ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($u = mysqli_fetch_assoc($recent_logs_query)) { 
                        $u_type = $u['type'];
                        $is_positive_save = ($u['save_unit'] >= 0);
                        $review_status = $u['review_status'];
                    ?>
                        <tr>
                            <td><?php echo date('m/Y', strtotime($u['record_date'])); ?></td>
                            <td><?php echo $types[$u_type]['name']; ?></td>
                            <td><?php echo number_format($u['meter_start'], 2); ?> - <?php echo number_format($u['meter_read'], 2); ?></td>
                            <td><?php echo number_format($u['amount'], 2); ?></td>
                            <td><?php echo number_format($u['cost'], 2); ?></td>
                            <td class="<?php echo $is_positive_save ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($u['save_unit'], 2); ?>
                            </td>
                            <td><?php echo number_format($u['co2e_save'], 2); ?></td>
                            <td>
                                <?php if ($u['evidence_url'] != '') { ?>
                                    <a href="uploads/<?php echo htmlspecialchars($u['evidence_url']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">ดูรูป</a>
                                <?php } ?>
                                <?php if ($u['evidence_prev_url'] != '') { ?>
                                    <a href="uploads/<?php echo htmlspecialchars($u['evidence_prev_url']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">เดือนก่อน</a>
                                <?php } ?>
                            </td>
                            <td>
                                <?php 
                                if ($review_status == 'Y') {
                                    echo '<span class="badge bg-success">อนุมัติ</span>';
                                } else if ($review_status == 'R') {
                                    echo '<span class="badge bg-danger">ไม่อนุมัติ</span>';
                                } else {
                                    echo '<span class="badge bg-warning text-dark">รอตรวจ</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (mysqli_num_rows($recent_logs_query) == 0) { ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">ยังไม่มีข้อมูลการบันทึก</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ป็อปอัป (Modal) สำหรับบันทึกข้อมูลค่าน้ำ-ค่าไฟ แยกตามประเภท -->
    <?php 
    foreach ($types as $t_key => $t_info) { 
        $has_previous = isset($has_previous_record[$t_key]);
        $default_month = $redo_month[$t_key];
    ?>
    <div class="modal fade" id="addModal_<?php echo $t_key; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="utility.php" enctype="multipart/form-data">
                    <input type="hidden" name="rec_type" value="<?php echo $t_key; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">บันทึก<?php echo $t_info['title']; ?>ประจำเดือน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small">
                            <b>ใช้เดือนก่อน</b> = ปริมาณที่ใช้ไปในเดือนที่แล้ว สำหรับนำมาเปรียบเทียบกับเดือนนี้ หากใช้น้อยลงจะช่วยประหยัดเงินและลดคาร์บอน
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ประจำเดือน</label>
                            <input type="month" name="record_month" class="form-control" value="<?php echo $default_month; ?>" max="<?php echo date('Y-m'); ?>" required>
                        </div>

                        <div class="border rounded p-3 mb-3 bg-light">
                            <h6 class="fw-bold mb-3"><?php echo $t_info['name']; ?> (หน่วย: <?php echo $t_info['unit']; ?>)</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small">เลขมิเตอร์ต้นเดือน (หรือสิ้นเดือนที่แล้ว)</label>
                                    <input type="number" step="0.01" min="0" name="start_<?php echo $t_key; ?>" class="form-control" value="<?php echo $form_defaults[$t_key]['start']; ?>" required <?php if ($has_previous) { echo 'disabled'; } ?>>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">เลขมิเตอร์ปลายเดือนนี้</label>
                                    <input type="number" step="0.01" min="0" name="end_<?php echo $t_key; ?>" class="form-control" placeholder="กรอกเลขมิเตอร์ล่าสุด" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">ราคาต่อหน่วย (บาท)</label>
                                    <input type="number" step="0.01" min="0" name="price_<?php echo $t_key; ?>" class="form-control" value="<?php echo $form_defaults[$t_key]['price']; ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">ปริมาณที่ใช้เดือนก่อน (<?php echo $t_info['unit']; ?>)</label>
                                    <input type="number" step="0.01" min="0" name="prev_<?php echo $t_key; ?>" class="form-control" value="<?php echo $form_defaults[$t_key]['prev']; ?>" required <?php if ($has_previous) { echo 'disabled'; } ?>>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label small">หลักฐานสลิป<?php echo $t_info['title']; ?> (ประจำเดือนนี้)</label>
                                <input type="file" name="evidence_<?php echo $t_key; ?>" accept="image/*" class="form-control">
                            </div>

                            <?php if (!$has_previous) { ?>
                                <div class="mt-3">
                                    <label class="form-label small">หลักฐานสลิป<?php echo $t_info['title']; ?> (ประจำเดือนก่อนหน้า)</label>
                                    <input type="file" name="evidence_prev_<?php echo $t_key; ?>" accept="image/*" class="form-control">
                                </div>
                            <?php } ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">หมายเหตุเพิ่มเติม</label>
                            <input type="text" name="note" class="form-control" placeholder="ระบุหมายเหตุ (ถ้ามี)">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include 'footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
