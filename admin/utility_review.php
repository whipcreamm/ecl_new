<?php
session_start();
include '../config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:../login.php');
    exit();
}
$cid = (int)$_SESSION['company_id'];

$me = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$cid"));
if (!$me || $me['role_id'] != 1) {
    header('location:../login.php');
    exit();
}

if(isset($_POST['review_id'])){
    $id = (int)$_POST['review_id'];
    $approve = ($_POST['action'] ?? '') == 'approve';
    $reason = trim($_POST['reason'] ?? '');
    $reason_sql = mysqli_real_escape_string($conn, $reason);

    $row = mysqli_fetch_assoc(mysqli_query($conn, "select * from utility_logs where id=$id and review_status='N'"));
    if($row){
        $new_status = $approve ? 'Y' : 'R';
        mysqli_query($conn, "update utility_logs set review_status='$new_status', review_note='$reason_sql' where id=$id");

        $to = (int)$row['company_id'];
        $name = $row['type'] == 'elec' ? 'ค่าไฟ' : 'ค่าน้ำ';
        $month = date('m/Y', strtotime($row['record_date']));
        $text = "หลักฐาน$name ประจำเดือน $month " . ($approve ? 'ได้รับการอนุมัติแล้ว' : "ไม่ผ่านการตรวจสอบ เหตุผล: $reason");
        $text = mysqli_real_escape_string($conn, $text);
        mysqli_query($conn, "insert into noti(recip_id,message,type,link) values ($to,'$text','utility','utility.php')");

        $_SESSION['flash'] = $approve ? ['success', 'อนุมัติหลักฐานเรียบร้อยแล้ว'] : ['warning', 'ไม่อนุมัติ และแจ้งผู้ใช้เรียบร้อยแล้ว'];
    }else{
        $_SESSION['flash'] = ['warning', 'ไม่พบรายการ หรือรายการนี้ตรวจไปแล้ว'];
    }
    // กลับมาที่ฟิวเตอร์เดิม
    $back = $_POST['ft'] ?? '';
    header('location: utility_review.php' . ($back != '' ? '?type=' . urlencode($back) : ''));
    exit();
}

$alert = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ฟิวเตอร์หมวด: elec / water / ว่าง = ทั้งหมด
$filter = $_GET['type'] ?? '';
if($filter != 'elec' && $filter != 'water') $filter = '';
$where = $filter != '' ? "where type='$filter'" : '';

// เก็บบริษัทไว้ใน array ตาม id
$cos = [];
$r = mysqli_query($conn, "select * from company");
while($x = mysqli_fetch_assoc($r)) $cos[$x['id']] = $x;

$log_result = mysqli_query($conn, "select * from utility_logs $where order by (review_status='N') desc, record_date desc, id desc");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>utility review</title>
    <?php include '../navbar.php'; ?>
    <style>
        .bg-orange{
            background: #f87d09;
        }
    </style>
</head>
<body class="bg-light">

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ตรวจหลักฐานบันทึกค่าน้ำ-ค่าไฟ</h3>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="utility_review.php" class="btn <?= $filter == '' ? 'btn-dark' : 'btn-dark' ?>">ทั้งหมด</a>
            <a href="utility_review.php?type=elec" class="btn <?= $filter == 'elec' ? 'btn-warning' : 'btn-warning' ?>">⚡ ไฟฟ้า</a>
            <a href="utility_review.php?type=water" class="btn <?= $filter == 'water' ? 'btn-primary' : 'btn-primary' ?>">💧 น้ำ</a>
        </div>

        <?php if($alert): ?>
            <div class="alert alert-<?= $alert[0] ?> p-2 text-center mt-3"><?= $alert[1] ?></div>
        <?php endif; ?>

        <div class="card shadow-sm p-3 mt-3">
            <table class="table table-hover table-bordered align-middle">
                <thead class="bg-light">
                    <tr class="text-center">
                        <th>ชื่อบริษัท</th>
                        <th>หมวด</th>
                        <th>ประจำเดือน</th>
                        <th>มิเตอร์ ต้น-ปลาย</th>
                        <th>ใช้ไป</th>
                        <th>เดือนก่อน</th>
                        <th>หลักฐาน</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($l = mysqli_fetch_assoc($log_result)): ?>
                    <?php $co = $cos[$l['company_id']]; ?>
                    <tr class="text-center">
                        <td class="text-start"><?= $co['name'] ?></td>
                        <td><b class="badge <?= $l['type'] == 'elec' ? 'bg-orange' : 'bg-primary' ?>"><?= $l['type'] == 'elec' ? 'ไฟฟ้า' : 'น้ำ' ?></b></td>
                        <td><?= date('m/Y', strtotime($l['record_date'])) ?></td>
                        <td><?= number_format($l['meter_start'], 2) ?> - <?= number_format($l['meter_read'], 2) ?></td>
                        <td class="text-success fw-bold"><?= number_format($l['amount'], 2) ?></td>
                        <td><?= number_format($l['prev_amount'], 2) ?></td>
                        <td class="text-nowrap">
                            <?php if(!empty($l['evidence_url'])): ?>
                                <a href="../uploads/<?= $l['evidence_url'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm">เดือนนี้</a>
                            <?php endif; ?>
                            <?php if(!empty($l['evidence_prev_url'])): ?>
                                <a href="../uploads/<?= $l['evidence_prev_url'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm">เดือนก่อน</a>
                            <?php endif; ?>
                            <?php if(empty($l['evidence_url']) && empty($l['evidence_prev_url'])): ?>
                                <span class="text-muted small">ไม่มีรูป</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($l['review_status'] == 'Y'): ?>
                                <span class="badge bg-success">อนุมัติแล้ว</span>
                            <?php elseif($l['review_status'] == 'R'): ?>
                                <span class="badge bg-danger">ไม่อนุมัติ</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">รอตรวจ</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($l['review_status'] == 'Y'): ?>
                                <button class="btn btn-sm btn-secondary" disabled>อนุมัติแล้ว</button>
                            <?php elseif($l['review_status'] == 'R'): ?>
                                <button class="btn btn-sm btn-secondary" disabled>ไม่อนุมัติ: <?= $l['review_note'] ?></button>
                            <?php else: ?>
                                <form method="post">
                                    <input type="hidden" name="review_id" value="<?= $l['id'] ?>">
                                    <input type="hidden" name="ft" value="<?= $filter ?>">
                                    <input type="text" name="reason" class="form-control form-control-sm mb-1" placeholder="เหตุผลถ้าไม่อนุมัติ" required onkeydown="if(event.key==='Enter') event.preventDefault()">
                                    <div class="d-flex gap-1">
                                        <button name="action" value="approve" formnovalidate class="btn btn-sm btn-success w-100" onclick="return confirm('ยืนยันอนุมัติหลักฐานนี้ใช่หรือไม่?')">อนุมัติ</button>
                                        <button name="action" value="reject" class="btn btn-sm btn-danger w-100" onclick="return confirm('ยืนยันไม่อนุมัติหลักฐานนี้ใช่หรือไม่?')">ไม่อนุมัติ</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if(mysqli_num_rows($log_result) == 0): ?>
            <p class="text-muted text-center mt-4">ยังไม่มีรายการบันทึกค่าน้ำ-ค่าไฟ</p>
        <?php endif; ?>
    </div>
<?php include '../footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>