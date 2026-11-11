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

// ตรวจสอบว่ามีแพ็กเกจพรีเมียม (pack_id = 2) ที่อนุมัติแล้วและยังไม่หมดอายุหรือไม่
$sql_check_premium = "SELECT * FROM members WHERE company_id = $cid AND status_mem = 'Y' AND pack_id = 2 AND (end_mem IS NULL OR end_mem >= CURDATE())";
$premium_query = mysqli_query($conn, $sql_check_premium);
$has_premium = mysqli_num_rows($premium_query);

if ($has_premium == 0) {
    header('location: pack.php');
    exit();
}

// เมื่อมีการส่งข้อมูลแบบ POST (กดเข้าร่วม หรือ ส่งหลักฐานภารกิจ)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mission_id = (int)$_POST['mission_id'];
    $action = $_POST['action'];
    $status_redirect = 'not_found';

    // ตรวจสอบว่ามีภารกิจนี้อยู่ในระบบจริงหรือไม่
    $sql_check_mission = "SELECT * FROM mission WHERE id = $mission_id";
    $mission_exists_query = mysqli_query($conn, $sql_check_mission);
    $mission_exists = mysqli_num_rows($mission_exists_query);

    if ($mission_exists > 0) {
        if ($action == 'join') {
            // ตรวจสอบว่าเคยเข้าร่วมภารกิจนี้ไปแล้วหรือยัง
            $sql_check_joined = "SELECT * FROM mission_parti WHERE mission_id = $mission_id AND company_id = $cid";
            $joined_query = mysqli_query($conn, $sql_check_joined);
            $already_joined = mysqli_num_rows($joined_query);

            if ($already_joined == 0) {
                $sql_insert_join = "INSERT INTO mission_parti (mission_id, company_id, status) VALUES ($mission_id, $cid, 'join')";
                mysqli_query($conn, $sql_insert_join);
            }
            $status_redirect = 'join';

        } else if ($action == 'submit') {
            // จัดการอัปโหลดไฟล์ภาพหลักฐาน
            $uploaded_file_name = '';

            if (isset($_FILES['evidence_url']) && $_FILES['evidence_url']['name'] != '') {
                $original_filename = $_FILES['evidence_url']['name'];
                $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');

                if (in_array($file_extension, $allowed_extensions)) {
                    // สร้างโฟลเดอร์ uploads หากยังไม่มี
                    if (!is_dir('uploads')) {
                        mkdir('uploads', 0777, true);
                    }

                    $uploaded_file_name = uniqid('ev_') . '.' . $file_extension;
                    $target_file_path = 'uploads/' . $uploaded_file_name;
                    move_uploaded_file($_FILES['evidence_url']['tmp_name'], $target_file_path);
                }
            }

            $note = '';
            if (isset($_POST['note'])) {
                $note = mysqli_real_escape_string($conn, trim($_POST['note']));
            }

            // อัปเดตสถานะการเข้าร่วมเป็นรอตรวจสอบ (pending)
            $sql_update_parti = "UPDATE mission_parti SET status = 'pending', note = '$note', evidence_url = '$uploaded_file_name', submit_at = NOW() WHERE mission_id = $mission_id AND company_id = $cid";
            mysqli_query($conn, $sql_update_parti);

            $status_redirect = 'submit';
        }
    }

    // รีไดเรกต์กลับมาที่หน้า mission พร้อมแนบสถานะ
    header('location: mission.php?status=' . $status_redirect);
    exit();
}

// กำหนดข้อความแจ้งเตือนตาม status ใน URL
$alert_type = '';
$alert_text = '';

if (isset($_GET['status'])) {
    $current_status = $_GET['status'];

    if ($current_status == 'join') {
        $alert_type = 'success';
        $alert_text = 'เข้าร่วมภารกิจเรียบร้อยแล้ว สามารถแนบภาพหลักฐานได้ทันที';
    } else if ($current_status == 'submit') {
        $alert_type = 'success';
        $alert_text = 'ส่งหลักฐานเรียบร้อยแล้ว รอผู้ดูแลระบบตรวจสอบ';
    } else if ($current_status == 'not_found') {
        $alert_type = 'danger';
        $alert_text = 'ไม่พบภารกิจ หรือภารกิจนี้สิ้นสุดลงแล้ว';
    }
}

// รับค่าคำค้นหาภารกิจ
$search_keyword = '';
if (isset($_GET['find'])) {
    $search_keyword = trim($_GET['find']);
}

$escaped_keyword = mysqli_real_escape_string($conn, $search_keyword);
$where_condition = '';
if ($search_keyword != '') {
    $where_condition = "WHERE title LIKE '%$escaped_keyword%' OR detail LIKE '%$escaped_keyword%'";
}

$sql_all_missions = "SELECT * FROM mission $where_condition ORDER BY id DESC";
$mission_result = mysqli_query($conn, $sql_all_missions);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ภารกิจลดคาร์บอน - Ecolyn</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ภารกิจลดคาร์บอน</h3>

        <?php if ($alert_text != '') { ?>
            <div class="alert alert-<?php echo $alert_type; ?> p-2 text-center mt-3">
                <?php echo $alert_text; ?>
            </div>
        <?php } ?>

        <!-- ฟอร์มค้นหาภารกิจ -->
        <form class="row g-2 mt-2" method="get" action="mission.php">
            <div class="col-md-8">
                <input name="find" class="form-control" placeholder="ค้นหาภารกิจ..." value="<?php echo htmlspecialchars($search_keyword); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-green w-100">ค้นหา</button>
            </div>
            <div class="col-md-2">
                <a href="mission.php" class="btn btn-outline-secondary w-100">ล้างค้นหา</a>
            </div>
        </form>

        <!-- รายการภารกิจ -->
        <div class="row">
            <?php 
            while ($m = mysqli_fetch_assoc($mission_result)) { 
                $mission_id = (int)$m['id'];

                // ค้นหาสถานะการเข้าร่วมของบริษัทนี้
                $sql_parti = "SELECT * FROM mission_parti WHERE mission_id = $mission_id AND company_id = $cid";
                $parti_query = mysqli_query($conn, $sql_parti);
                $parti_row = mysqli_fetch_assoc($parti_query);

                $participant_status = '';
                if ($parti_row && isset($parti_row['status'])) {
                    $participant_status = $parti_row['status'];
                }
            ?>
                <div class="col-md-6 mt-3">
                    <div class="card card-white shadow-sm p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($m['title']); ?></h5>
                            <p class="text-success fw-bold fs-5 mb-0">+<?php echo number_format($m['point_reward']); ?> แต้ม</p>
                        </div>
                        <div class="mt-3 border-top pt-2">
                            <p class="text-muted small mb-2"><?php echo nl2br(htmlspecialchars($m['detail'])); ?></p>
                            <p class="small mb-0">
                                ลด CO₂ ประมาณ <b><?php echo number_format($m['co2e_save'], 2); ?></b> kgCO₂e
                                <?php if (!empty($m['end_date'])) { ?>
                                    | ถึงวันที่ <?php echo date('d/m/Y', strtotime($m['end_date'])); ?>
                                <?php } ?>
                            </p>
                        </div>
                        <div class="mt-auto pt-3">
                            <?php if ($participant_status == 'pending') { ?>
                                <button type="button" class="btn btn-warning w-100" disabled>รอเจ้าหน้าที่ตรวจหลักฐาน</button>
                            <?php } else if ($participant_status == 'complete') { ?>
                                <button type="button" class="btn btn-secondary w-100" disabled>ภารกิจสำเร็จเรียบร้อย</button>
                            <?php } else if ($participant_status == 'join' || $participant_status == 'reject') { ?>
                                <?php if ($participant_status == 'reject') { ?>
                                    <div class="alert alert-danger py-1 small text-center mb-2">
                                        หลักฐานไม่ผ่านการอนุมัติ สามารถแนบหลักฐานและส่งใหม่ได้
                                    </div>
                                <?php } ?>
                                <!-- ฟอร์มส่งหลักฐานภารกิจ -->
                                <form method="post" action="mission.php" enctype="multipart/form-data">
                                    <input type="hidden" name="mission_id" value="<?php echo $mission_id; ?>">
                                    <div class="mb-2">
                                        <label class="form-label small">แนบรูปภาพหลักฐาน</label>
                                        <input type="file" name="evidence_url" accept="image/*" class="form-control" required>
                                    </div>
                                    <div class="mb-2">
                                        <input type="text" name="note" class="form-control" placeholder="คำอธิบายเพิ่มเติม (ถ้ามี)">
                                    </div>
                                    <button type="submit" name="action" value="submit" class="btn btn-success w-100">ส่งหลักฐาน</button>
                                </form>
                            <?php } else { ?>
                                <!-- ฟอร์มกดเข้าร่วมภารกิจ -->
                                <form method="post" action="mission.php">
                                    <input type="hidden" name="mission_id" value="<?php echo $mission_id; ?>">
                                    <button type="submit" name="action" value="join" class="btn btn-success w-100">เข้าร่วมภารกิจ</button>
                                </form>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php 
        $mission_total = mysqli_num_rows($mission_result);
        if ($mission_total == 0) { 
        ?>
            <p class="text-muted text-center mt-4">ยังไม่มีภารกิจที่เปิดให้เข้าร่วมในขณะนี้</p>
        <?php } ?>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include 'footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
