<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include '../config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ถ้ายังไม่ล็อกอินให้กลับไปหน้า login
$company_id = 0;
if (isset($_SESSION['company_id'])) {
    $company_id = (int)$_SESSION['company_id'];
}

$sql_company = "SELECT * FROM company WHERE id = $company_id";
$company_query = mysqli_query($conn, $sql_company);
$company = mysqli_fetch_assoc($company_query);

if (!$company || $company['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

// เมื่อมีการส่งข้อมูลแบบ POST จากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ลบภารกิจ
    if (isset($_POST['delete'])) {
        $del_id = (int)$_POST['delete'];
        $sql_del = "DELETE FROM mission WHERE id = $del_id";
        $del_result = mysqli_query($conn, $sql_del);

        if ($del_result) {
            $_SESSION['msg'] = 'ลบข้อมูลภารกิจสำเร็จ';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'ลบข้อมูลภารกิจไม่สำเร็จ';
            $_SESSION['msg_type'] = 'danger';
        }
    }

    // 2. แก้ไขภารกิจ
    if (isset($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $title = mysqli_real_escape_string($conn, trim($_POST['title']));
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail']));
        $descrip = mysqli_real_escape_string($conn, trim($_POST['descrip']));
        $point_reward = (int)$_POST['point_reward'];
        $co2e_save = (float)$_POST['co2e_save'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'];

        $sql_edit = "UPDATE mission SET title = '$title', detail = '$detail', descrip = '$descrip', point_reward = '$point_reward', co2e_save = '$co2e_save', start_date = '$start_date', end_date = '$end_date', status = '$status' WHERE id = $id";
        $edit_result = mysqli_query($conn, $sql_edit);

        if ($edit_result) {
            $_SESSION['msg'] = 'แก้ไขข้อมูลภารกิจสำเร็จ';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'แก้ไขข้อมูลภารกิจไม่สำเร็จ';
            $_SESSION['msg_type'] = 'danger';
        }
    }

    // 3. เพิ่มภารกิจใหม่
    if (isset($_POST['add_mission'])) {
        $title = mysqli_real_escape_string($conn, trim($_POST['title']));
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail']));
        $descrip = mysqli_real_escape_string($conn, trim($_POST['descrip']));
        $point_reward = (int)$_POST['point_reward'];
        $co2e_save = (float)$_POST['co2e_save'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';

        $sql_insert = "INSERT INTO mission (title, detail, descrip, status, point_reward, co2e_save, start_date, end_date, create_at) VALUES ('$title', '$detail', '$descrip', '$status', '$point_reward', '$co2e_save', '$start_date', '$end_date', NOW())";
        $insert_result = mysqli_query($conn, $sql_insert);

        if ($insert_result) {
            $_SESSION['msg'] = 'เพิ่มข้อมูลภารกิจสำเร็จ';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'เพิ่มข้อมูลภารกิจไม่สำเร็จ';
            $_SESSION['msg_type'] = 'danger';
        }
    }

    header('location: mission_edit.php');
    exit();
}

// ดึงข้อความแจ้งเตือนจากเซสชัน
$msg = '';
$msg_type = 'info';

if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    $msg_type = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : 'info';
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}

// ดึงรายการภารกิจทั้งหมด เรียงจากใหม่ไปเก่า
$sql_mission = "SELECT * FROM mission ORDER BY id DESC";
$mission_result = mysqli_query($conn, $sql_mission);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการภารกิจ - Ecolyn Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">จัดการภารกิจลดคาร์บอน</h4>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMissionModal">+ เพิ่มภารกิจใหม่</button>
        </div>

        <?php if ($msg != '') { ?>
            <div class="alert alert-<?php echo $msg_type; ?> py-2 small text-center mb-3">
                <h6 class="mb-0"><?php echo htmlspecialchars($msg); ?></h6>
            </div>
        <?php } ?>

        <div class="card shadow-sm p-3">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-start">ชื่อภารกิจ</th>
                            <th class="text-start">รายละเอียด</th>
                            <th>แต้มรางวัล</th>
                            <th>CO₂ ลดได้ (kg)</th>
                            <th>วันที่เริ่ม</th>
                            <th>วันที่สิ้นสุด</th>
                            <th>สถานะ</th>
                            <th style="min-width: 140px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($m = mysqli_fetch_assoc($mission_result)) { 
                        $mid = (int)$m['id'];
                        $is_active = ($m['status'] == 'active');
                        $start_date_display = !empty($m['start_date']) ? date('d/m/Y', strtotime($m['start_date'])) : '-';
                        $end_date_display = !empty($m['end_date']) ? date('d/m/Y', strtotime($m['end_date'])) : '-';
                    ?>
                        <tr>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($m['title']); ?></td>
                            <td class="text-start"><small><?php echo htmlspecialchars($m['detail']); ?></small></td>
                            <td class="text-success fw-bold">+<?php echo number_format($m['point_reward']); ?></td>
                            <td><?php echo number_format($m['co2e_save'], 2); ?></td>
                            <td><?php echo $start_date_display; ?></td>
                            <td><?php echo $end_date_display; ?></td>
                            <td>
                                <?php if ($is_active) { ?>
                                    <span class="badge bg-success">เปิด</span>
                                <?php } else { ?>
                                    <span class="badge bg-danger">ปิด</span>
                                <?php } ?>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#editModal_<?php echo $mid; ?>">แก้ไข</button>
                                    <form method="post" action="mission_edit.php" onsubmit="return confirm('ยืนยันการลบภารกิจนี้?')">
                                        <input type="hidden" name="delete" value="<?php echo $mid; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- ป็อปอัป (Modal) แก้ไขภารกิจ -->
                        <div class="modal fade" id="editModal_<?php echo $mid; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post" action="mission_edit.php">
                                        <input type="hidden" name="edit_id" value="<?php echo $mid; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">แก้ไขภารกิจ</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-start">
                                            <div class="mb-3">
                                                <label class="form-label">ชื่อภารกิจ</label>
                                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($m['title']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">รายละเอียดโดยย่อ</label>
                                                <input type="text" name="detail" class="form-control" value="<?php echo htmlspecialchars($m['detail']); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">คำอธิบายภารกิจ/เงื่อนไข</label>
                                                <textarea name="descrip" class="form-control" rows="3"><?php echo htmlspecialchars($m['descrip']); ?></textarea>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <label class="form-label">แต้มรางวัล</label>
                                                    <input type="number" name="point_reward" class="form-control" value="<?php echo (int)$m['point_reward']; ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">CO₂ ที่ลดได้ (kg)</label>
                                                    <input type="number" step="0.01" name="co2e_save" class="form-control" value="<?php echo (float)$m['co2e_save']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <label class="form-label">วันที่เริ่ม</label>
                                                    <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d', strtotime($m['start_date'])); ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">วันที่สิ้นสุด</label>
                                                    <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime($m['end_date'])); ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">สถานะ</label>
                                                <select name="status" class="form-select">
                                                    <option value="active" <?php if ($m['status'] == 'active') { echo 'selected'; } ?>>เปิดให้ทำภารกิจ (active)</option>
                                                    <option value="inactive" <?php if ($m['status'] == 'inactive') { echo 'selected'; } ?>>ปิด (inactive)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                            <button type="submit" class="btn btn-success">บันทึกการแก้ไข</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (mysqli_num_rows($mission_result) == 0) { ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">ยังไม่มีรายการภารกิจ</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ป็อปอัป (Modal) เพิ่มภารกิจใหม่ -->
    <div class="modal fade" id="addMissionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="mission_edit.php">
                    <input type="hidden" name="add_mission" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">เพิ่มภารกิจใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label">ชื่อภารกิจ</label>
                            <input type="text" name="title" class="form-control" placeholder="กรอกชื่อภารกิจ" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียดโดยย่อ</label>
                            <input type="text" name="detail" class="form-control" placeholder="กรอกรายละเอียดสั้นๆ">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">คำอธิบายภารกิจ/เงื่อนไข</label>
                            <textarea name="descrip" class="form-control" rows="3" placeholder="ระบุเงื่อนไขการส่งหลักฐาน"></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">แต้มรางวัล</label>
                                <input type="number" name="point_reward" class="form-control" value="50" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">CO₂ ที่ลดได้ (kg)</label>
                                <input type="number" step="0.01" name="co2e_save" class="form-control" value="1" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">วันที่เริ่ม</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">วันที่สิ้นสุด</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สถานะ</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>เปิดให้ทำภารกิจ (active)</option>
                                <option value="inactive">ปิด (inactive)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success">เพิ่มภารกิจ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include '../footer.php'; ?>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
