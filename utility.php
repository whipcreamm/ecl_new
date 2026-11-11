<?php
session_start();
include 'config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:login.php');
    exit();
}
$cid = $_SESSION['company_id'];

// ต้องมีแพ็กเกจที่ใช้งานอยู่
$has_pack = mysqli_num_rows(mysqli_query($conn, "select * from members where company_id=$cid and status_mem='Y' and (end_mem is null or end_mem >= curdate())"));
if($has_pack == 0){
    header('location: package.php');
    exit();
}

// ค่าตั้งค่าระบบ
$set = [];
$r = mysqli_query($conn, "select * from eco_setting");
while($s = mysqli_fetch_assoc($r)) $set[$s['setting_key']] = $s['setting_value'];
$ppk = $set['point_per_kg'] ?? 10;

// ชื่อ, หน่วย, factor, ราคาตั้งต้น, ชื่อสลิป
$types = [
    'elec'  => ['⚡ ไฟฟ้า', 'kWh', $set['factor_elec'] ?? 0.4999, 4.5, 'ค่าไฟ'],
    'water' => ['💧 น้ำ', 'ลบ.ม.', $set['factor_water'] ?? 0.7, 18, 'ค่าน้ำ'],
];

// ดึงบันทึกของบริษัทเราทั้งหมด แล้วคำนวณใน PHP
$all = [];
$rejected = [];
$rej_months = [];   // หมวด => เดือนที่ถูกปฏิเสธ
$r = mysqli_query($conn, "select * from utility_logs where company_id=$cid order by record_date, id");
while($x = mysqli_fetch_assoc($r)){
    $all[] = $x;
    if($x['review_status'] == 'R'){
        $rejected[] = $x;
        $rej_months[$x['type']][date('Y-m', strtotime($x['record_date']))] = 1;
    }
}

// เดือนที่ให้กรอกใหม่ แยกตามหมวด (ถ้าไม่มีที่ถูกปฏิเสธ ใช้เดือนปัจจุบัน)
foreach($types as $t => $info) $redo[$t] = date('Y-m');
foreach(array_reverse($rejected) as $x) $redo[$x['type']] = date('Y-m', strtotime($x['record_date']));

// ข้อมูลที่ใช้ได้ (ตัดเดือนที่ถูกปฏิเสธ) + ค่าล่าสุดแต่ละหมวด
$has_prev = [];      // หมวด => เคยบันทึกแล้ว
$last = [];          // หมวด => แถวล่าสุด
$tot_prev = 0;       // สรุป 1 ปี
$tot_now = 0;
$year_months = [];   // เดือนที่บันทึกใน 1 ปี
$one_year_ago = date('Y-m-d', strtotime('-12 month'));
foreach($all as $x){
    $m = date('Y-m', strtotime($x['record_date']));
    if(isset($rej_months[$x['type']][$m])) continue;
    $has_prev[$x['type']] = true;
    $last[$x['type']] = $x;   // เรียงเก่า->ใหม่ ตัวสุดท้ายจะเป็นล่าสุด
    if($x['record_date'] > $one_year_ago){
        $tot_now += $x['amount'] * $types[$x['type']][2];
        $tot_prev += $x['prev_amount'] * $types[$x['type']][2];
        $year_months[$m] = 1;
    }
}

// ค่าตั้งต้นในฟอร์ม
foreach($types as $t => $info){
    $l = $last[$t] ?? null;
    $def[$t] = ['start' => $l['meter_read'] ?? 0, 'prev' => $l['amount'] ?? 0, 'price' => $l['unit_price'] ?? $info[3]];
}

// ขอใบรับรอง
$percent = $tot_prev > 0 ? ($tot_prev - $tot_now) / $tot_prev * 100 : 0;
$months = count($year_months);
$can_cert = $percent >= 10 && $months >= 10;

$company = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$cid"));
$cname = $company['name'];
$asked = false;
$r = mysqli_query($conn, "select * from noti where type='cert'");
while($n = mysqli_fetch_assoc($r)){
    if(strpos($n['message'], $cname) !== false && $n['create_at'] >= date('Y-m-d H:i:s', strtotime('-30 day'))) $asked = true;
}

$errors = [];
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    // ขอใบรับรอง
    if(isset($_POST['request_cert'])){
        if($can_cert){
            mysqli_query($conn, "update members set cert_status='N', cert_date_req=curdate() where company_id=$cid");
            $text = "องค์กร '$cname' ยื่นคำขอรับใบรับรอง (ลดคาร์บอนได้ " . number_format($percent, 1) . "% บันทึกครบ $months เดือน)";
            $admins = mysqli_query($conn, "select * from company where role_id=1");
            while($ad = mysqli_fetch_assoc($admins)){
                mysqli_query($conn, "insert into noti(recip_id,message,type,link) values({$ad['id']},'$text','cert','admin/certificate.php')");
            }
            $_SESSION['msg'] = ['success', 'ส่งคำขอรับใบรับรองเรียบร้อยแล้ว'];
        }else{
            $_SESSION['msg'] = ['danger', 'ยังไม่ถึงเกณฑ์ (ลดคาร์บอน ' . number_format($percent, 1) . '% จาก 10% | บันทึก ' . $months . '/10 เดือน)'];
        }
        header('location: utility.php');
        exit();
    }

    // บันทึกรายเดือน (แยกหมวด)
    $t = $_POST['rec_type'];
    if(!isset($types[$t])) exit();
    $info = $types[$t];
    $month = $_POST['record_month'];
    $note = $_POST['note'];
    $mt = date('m/Y', strtotime("$month-01"));
    $hp = isset($has_prev[$t]);

    // เช็คเดือนนี้ของหมวดนี้ว่าเคยบันทึก / เคยถูกปฏิเสธไหม
    $has_reject = isset($rej_months[$t][$month]);
    $dup = false;
    foreach($all as $x){
        if($x['type'] == $t && date('Y-m', strtotime($x['record_date'])) == $month) $dup = true;
    }

    if($month > date('Y-m')) $errors[] = 'ไม่สามารถบันทึกเดือนในอนาคตได้';
    elseif(!$has_reject && $dup) $errors[] = "คุณบันทึกข้อมูล{$info[4]}ประจำเดือน $mt ไปแล้ว";

    $start = $hp ? $def[$t]['start'] : $_POST["start_$t"];
    $prev = $hp ? $def[$t]['prev'] : $_POST["prev_$t"];
    $end = $_POST["end_$t"];
    $price = $_POST["price_$t"];
    if($end < $start) $errors[] = "มิเตอร์{$info[0]}ปลายเดือนต้องไม่น้อยกว่าต้นเดือน";
    $used = $end - $start;
    $baht = ($prev - $used) * $price;
    $d = [$start, $end, $price, $prev, $used, $used * $price, $baht, ($prev - $used) * $info[2]];
    $tn = $used * $info[2];
    $tp = $prev * $info[2];
    $tb = $baht;

    if(!$errors){
        $reason = "ลดการใช้{$info[4]}ประจำเดือน $mt";

        // เดือนที่ถูกปฏิเสธ: คืนแต้มเก่า + ลบข้อมูลเก่า (เฉพาะหมวดนี้)
        if($has_reject){
            $old = 0;
            $pr = mysqli_query($conn, "select * from eco_point_ledger where company_id=$cid");
            while($p = mysqli_fetch_assoc($pr)){
                if($p['reason'] == $reason) $old += $p['point'];
            }
            $neg = -$old;
            if($neg != 0) mysqli_query($conn, "insert into eco_point_ledger(company_id,point,reason) values($cid,$neg,'$reason')");
            foreach($all as $x){
                if($x['type'] == $t && date('Y-m', strtotime($x['record_date'])) == $month){
                    mysqli_query($conn, "delete from utility_logs where id={$x['id']}");
                }
            }
        }

        // อัปโหลดสลิปเดือนนี้
        $pic = '';
        if($_FILES["evidence_$t"]['error'] === UPLOAD_ERR_OK){
            $ext = strtolower(pathinfo($_FILES["evidence_$t"]['name'], PATHINFO_EXTENSION));
            $pic = uniqid('ev_') . '.' . $ext;
            move_uploaded_file($_FILES["evidence_$t"]['tmp_name'], "uploads/$pic");
        }
        // อัปโหลดสลิปเดือนก่อน (เฉพาะครั้งแรก)
        $pic_prev = '';
        if(!$hp && $_FILES["evidence_prev_$t"]['error'] === UPLOAD_ERR_OK){
            $ext = strtolower(pathinfo($_FILES["evidence_prev_$t"]['name'], PATHINFO_EXTENSION));
            $pic_prev = uniqid('ev_') . '.' . $ext;
            move_uploaded_file($_FILES["evidence_prev_$t"]['tmp_name'], "uploads/$pic_prev");
        }
        mysqli_query($conn, "insert into utility_logs(company_id,type,meter_start,meter_read,unit_price,prev_amount,amount,cost,save_unit,co2e_save,evidence_url,evidence_prev_url,note,record_date) values($cid,'$t','$d[0]','$d[1]','$d[2]','$d[3]','$d[4]','$d[5]','$d[6]','$d[7]','$pic','$pic_prev','$note','$month-01')");

        $diff = $tp - $tn;
        $pct = $tp > 0 ? $diff / $tp * 100 : 0;
        $point = round(max(0, $diff) * $ppk);
        if($point > 0) mysqli_query($conn, "insert into eco_point_ledger(company_id,point,reason) values($cid,$point,'$reason')");

        $_SESSION['msg'] = ['success', "บันทึก{$info[4]} $mt สำเร็จ! ประหยัด " . number_format($tb, 2) . " บาท | ลดคาร์บอนได้ " . number_format($diff, 2) . " kg (" . number_format($pct, 1) . "%)" . ($point > 0 ? " +$point แต้ม" : "")];
        header('location: utility.php');
        exit();
    }
}

$msg = $_SESSION['msg'] ?? null;
unset($_SESSION['msg']);
$result = mysqli_query($conn, "select * from utility_logs where company_id=$cid order by record_date desc, id desc limit 20");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกค่าน้ำ-ค่าไฟ</title>
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">บันทึกการใช้น้ำ-ไฟฟ้า (รายเดือน)</h4>
        <div>
            <?php foreach($types as $t => $info): ?>
                <button type="button" class="btn <?= $t == 'elec' ? 'btn-warning' : 'btn-primary' ?>" data-bs-toggle="modal" data-bs-target="#addModal_<?= $t ?>">+ บันทึก<?= $info[4] ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if($msg): ?><div class="alert alert-<?= $msg[0] ?>"><?= $msg[1] ?></div><?php endif; ?>
    <?php foreach($errors as $e): ?><div class="alert alert-danger py-2"><?= $e ?></div><?php endforeach; ?>
    <?php foreach($rejected as $x): ?>
        <div class="alert alert-danger py-2">หลักฐาน<?= $types[$x['type']][4] ?> ประจำเดือน <?= date('m/Y', strtotime($x['record_date'])) ?> ไม่ผ่านการตรวจสอบ: <?= $x['review_note'] ?> — กรุณากรอกข้อมูล<?= $types[$x['type']][4] ?>เดือนนี้ใหม่ทั้งเดือน</div>
    <?php endforeach; ?>

    <div class="card shadow-sm mb-3 <?= $can_cert ? 'bg-warning-subtle' : '' ?>">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-1">ขอรับใบรับรองลดคาร์บอน</h6>
                <small class="text-muted d-block">เงื่อนไข: ลดคาร์บอนได้ 10% ขึ้นไป และบันทึกข้อมูลครบ 10 เดือน (ภายใน 1 ปี)</small>
                <small>
                    ลดได้ <b class="<?= $percent >= 10 ? 'text-success' : 'text-danger' ?>"><?= number_format($percent, 1) ?>%</b> ·
                    บันทึกแล้ว <b class="<?= $months >= 10 ? 'text-success' : 'text-danger' ?>"><?= $months ?>/10 เดือน</b>
                    <?php if($asked): ?><span class="badge bg-info text-dark">ส่งคำขอแล้ว</span><?php endif; ?>
                </small>
            </div>
            <form method="post" onsubmit="return confirm('ยืนยันการขอรับใบรับรอง?')">
                <button name="request_cert" value="1" class="btn <?= $can_cert ? 'btn-warning' : 'btn-secondary' ?>" <?= $can_cert ? '' : 'disabled' ?>>ขอรับใบรับรอง</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm p-3">
        <table class="table table-hover table-bordered align-middle">
            <thead class="bg-light">
                <tr class="text-center">
                    <th>ประจำเดือน</th><th>หมวด</th><th>มิเตอร์ ต้น-ปลาย</th><th>ใช้ไป</th><th>ค่าใช้จ่าย</th>
                    <th>ประหยัด (บาท)</th><th>CO₂ ลดได้ (kg)</th><th>หลักฐาน</th><th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
            <?php while($u = mysqli_fetch_assoc($result)): ?>
                <tr class="text-center">
                    <td><?= date('m/Y', strtotime($u['record_date'])) ?></td>
                    <td><?= $types[$u['type']][0] ?></td>
                    <td><?= number_format($u['meter_start'], 2) ?> - <?= number_format($u['meter_read'], 2) ?></td>
                    <td><?= number_format($u['amount'], 2) ?></td>
                    <td><?= number_format($u['cost'], 2) ?></td>
                    <td class="<?= $u['save_unit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($u['save_unit'], 2) ?></td>
                    <td><?= number_format($u['co2e_save'], 2) ?></td>
                    <td>
                        <?php if($u['evidence_url'] != ''): ?><a href="uploads/<?= $u['evidence_url'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">ดูรูป</a><?php endif; ?>
                        <?php if($u['evidence_prev_url'] != ''): ?><a href="uploads/<?= $u['evidence_prev_url'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">เดือนก่อน</a><?php endif; ?>
                    </td>
                    <td>
                        <?php if($u['review_status'] == 'Y'): ?><span class="badge bg-success">อนุมัติ</span>
                        <?php elseif($u['review_status'] == 'R'): ?><span class="badge bg-danger">ไม่อนุมัติ</span>
                        <?php else: ?><span class="badge bg-warning text-dark">รอตรวจ</span><?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach($types as $t => $info): ?>
<?php $hp = isset($has_prev[$t]); $same = ($_POST['rec_type'] ?? '') == $t; ?>
<div class="modal fade" id="addModal_<?= $t ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="rec_type" value="<?= $t ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">บันทึก<?= $info[4] ?>ประจำเดือน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small"><b>ใช้เดือนก่อน</b> = ปริมาณที่ใช้ไปในเดือนที่แล้ว เอามาเทียบกับเดือนนี้ ถ้าใช้น้อยกว่าเดือนก่อน จะลดคาร์บอนและประหยัดเงินได้</div>
                    <div class="mb-3">
                        <label class="form-label">ประจำเดือน</label>
                        <input type="month" name="record_month" class="form-control" value="<?= $same ? $_POST['record_month'] : $redo[$t] ?>" max="<?= date('Y-m') ?>" required>
                    </div>

                    <div class="border rounded p-3 mb-3 bg-light">
                        <h6 class="fw-bold"><?= $info[0] ?> (<?= $info[1] ?>)</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">มิเตอร์เดือนที่แล้ว</label>
                                <input type="number" step="0.01" min="0" name="start_<?= $t ?>" class="form-control" value="<?= $def[$t]['start'] ?>" required <?= $hp ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">มิเตอร์เดือนล่าสุด</label>
                                <input type="number" step="0.01" min="0" name="end_<?= $t ?>" class="form-control" value="<?= $same ? $_POST["end_$t"] : '' ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">ราคา/หน่วย (บาท)</label>
                                <input type="number" step="0.01" min="0" name="price_<?= $t ?>" class="form-control" value="<?= $same ? $_POST["price_$t"] : $def[$t]['price'] ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">ใช้เดือนก่อน (<?= $info[1] ?>)</label>
                                <input type="number" step="0.01" min="0" name="prev_<?= $t ?>" class="form-control" value="<?= $def[$t]['prev'] ?>" required <?= $hp ? 'disabled' : '' ?>>
                            </div>
                        </div>
                        <label class="form-label small mt-2">หลักฐานสลิป<?= $info[4] ?> (เดือนนี้)</label>
                        <input type="file" name="evidence_<?= $t ?>" accept="image/*" class="form-control">
                        <?php if(!$hp): ?>
                            <label class="form-label small mt-2">หลักฐานสลิป<?= $info[4] ?>เดือนก่อน</label>
                            <input type="file" name="evidence_prev_<?= $t ?>" accept="image/*" class="form-control">
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">หมายเหตุเพิ่มเติม</label>
                        <input type="text" name="note" class="form-control" value="<?= $same ? $_POST['note'] : '' ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>