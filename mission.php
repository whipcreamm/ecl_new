<?php
session_start();
include 'config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:login.php');
    exit();
}
$cid = (int)$_SESSION['company_id'];

// ต้องเป็นแพ็กเกจพรีเมียมที่อนุมัติแล้วและยังไม่หมดอายุ
$premium = mysqli_query($conn, "select * from members where company_id=$cid and status_mem='Y' and pack_id=2 and (end_mem is null or end_mem >= curdate())");
if (mysqli_num_rows($premium) == 0) {
    header('location:pack.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $mission_id = (int)$_POST['mission_id'];
    $action = $_POST['action'];
    $status = 'not_found';

    $has = mysqli_num_rows(mysqli_query($conn, "select * from mission where id=$mission_id"));
    if($has > 0){
        if($action == 'join'){
            $joined = mysqli_num_rows(mysqli_query($conn, "select * from mission_parti where mission_id=$mission_id and company_id=$cid"));
            if($joined == 0){
                mysqli_query($conn, "insert into mission_parti(mission_id,company_id,status) values ($mission_id,$cid,'join')");
            }
            $status = 'join';
        }elseif($action == 'submit'){
            $file_name = '';
            if($_FILES['evidence_url']['name'] != ''){
                $ext = strtolower(pathinfo($_FILES['evidence_url']['name'], PATHINFO_EXTENSION));
                if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){
                    if(!is_dir('uploads')){
                        mkdir('uploads', 0777, true);
                    }
                    $file_name = uniqid('ev_') . '.' . $ext;
                    move_uploaded_file($_FILES['evidence_url']['tmp_name'], 'uploads/' . $file_name);
                }
            }
            $note = mysqli_real_escape_string($conn, trim($_POST['note']));
            mysqli_query($conn, "update mission_parti set status='pending', note='$note', evidence_url='$file_name', submit_at=now()
                where mission_id=$mission_id and company_id=$cid");
            $status = 'submit';
        }
    }
    header("location: mission.php?status=$status");
    exit();
}
$st='';
$alert = [
    'join'      => ['success', 'เข้าร่วมภารกิจเรียบร้อยแล้ว สามารถแนบภาพหลักฐานได้ทันที'],
    'submit'    => ['success', 'ส่งหลักฐานเรียบร้อยแล้ว รอผู้ดูแลระบบตรวจสอบ'],
    'not_found' => ['danger', 'ไม่พบภารกิจ หรือภารกิจนี้สิ้นสุดลงแล้ว']
];
$alert = $alerts[$st] ?? null;

$find = trim($_GET['find'] ?? '');
$kw = mysqli_real_escape_string($conn, $find);
$where = $find != '' ? "where title like '%$kw%' or detail like '%$kw%'" : '';
$mission_result = mysqli_query($conn, "select * from mission $where order by id desc");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mission</title>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ภารกิจลดคาร์บอน</h3>

        <?php if($alert): ?>
                <div class="alert alert-<?= $alert[0] ?> p-2 text-center mt-3"><?= $alert[1] ?></div>
        <?php endif; ?>

        <form class="row g-2 mt-2" method="get">
            <div class="col-md-8">
                <input name="find" class="form-control" placeholder="ค้นหาภารกิจ..." value="<?= htmlspecialchars($find) ?>">
            </div>
            <div class="col-md-2"><button class="btn btn-green w-100">ค้นหา</button></div>
            <div class="col-md-2"><a href="mission.php" class="btn btn-outline-secondary w-100">ล้างค้นหา</a></div>
        </form>

        <div class="row">
            <?php while($m = mysqli_fetch_assoc($mission_result)): ?>
                <?php
                    $parti = mysqli_fetch_assoc(mysqli_query($conn, "select * from mission_parti where mission_id={$m['id']} and company_id=$cid"));
                    $ps = $parti['status'] ?? '';
                ?>
                <div class="col-md-6 mt-3">
                    <div class="card card-white shadow-sm p-3 h-100">
                        <div class="d-flex justify-content-between">
                            <h5 class="fw-bold"><?= $m['title'] ?></h5>
                            <p class="text-success fw-bold">+<?= (int)$m['point_reward'] ?> แต้ม</p>
                        </div>
                        <div class="mt-3 border-top">
                            <p class="text-muted small mb-1"><?= nl2br($m['detail']) ?></p>
                            <p class="small mb-0">
                                ลด CO₂ ประมาณ <b><?= number_format($m['co2e_save'], 2) ?></b> kgCO₂e
                                <?php if(!empty($m['end_date'])): ?>
                                    | ถึงวันที่ <?= date('d/m/Y', strtotime($m['end_date'])) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="mt-auto pt-3">
                            <?php if($ps == 'pending'): ?>
                                <button class="btn btn-warning w-100" disabled>รอเจ้าหน้าที่ตรวจหลักฐาน</button>
                            <?php elseif($ps == 'complete'): ?>
                                <button class="btn btn-secondary w-100" disabled>ภารกิจสำเร็จเรียบร้อย</button>
                            <?php elseif($ps == 'join' || $ps == 'reject'): ?>
                                <?php if($ps == 'reject'): ?>
                                    <div class="alert alert-danger py-1 small text-center">หลักฐานไม่ผ่านการอนุมัติ สามารถส่งใหม่ได้</div>
                                <?php endif; ?>
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="mission_id" value="<?= $m['id'] ?>">
                                    <input type="file" name="evidence_url" accept="image/*" class="form-control mb-2" required>
                                    <input name="note" class="form-control mb-2" placeholder="คำอธิบายเพิ่มเติม">
                                    <button name="action" value="submit" class="btn btn-success w-100">ส่งหลักฐาน</button>
                                </form>
                            <?php else: ?>
                                <form method="post">
                                    <input type="hidden" name="mission_id" value="<?= $m['id'] ?>">
                                    <button name="action" value="join" class="btn btn-success w-100">เข้าร่วมภารกิจ</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if(mysqli_num_rows($mission_result) == 0): ?>
            <p class="text-muted text-center mt-4">ยังไม่มีภารกิจที่เปิดให้เข้าร่วมในขณะนี้</p>
        <?php endif; ?>
    </div>
<?php include 'footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>