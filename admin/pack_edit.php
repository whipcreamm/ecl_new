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

// เมื่อมีการส่งข้อมูลแบบ POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. ลบข้อมูลแพ็กเกจ
    if (isset($_POST['delete'])) {
        $del_id = (int)$_POST['delete'];
        $sql_del = "DELETE FROM pack WHERE id = $del_id";
        $del_result = mysqli_query($conn, $sql_del);

        if ($del_result) {
            $_SESSION['msg'] = 'ลบข้อมูลแพ็กเกจเสร็จแล้ว';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'ลบข้อมูลแพ็กเกจไม่สำเร็จ';
            $_SESSION['msg_type'] = 'danger';
        }
    }

    // 2. แก้ไขข้อมูลแพ็กเกจ
    if (isset($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $price = (float)$_POST['price'];
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail']));

        $sql_edit = "UPDATE pack SET name = '$name', price = '$price', detail = '$detail' WHERE id = $id";
        $edit_result = mysqli_query($conn, $sql_edit);

        if ($edit_result) {
            $_SESSION['msg'] = 'แก้ไขข้อมูลแพ็กเกจเสร็จแล้ว';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'แก้ไขข้อมูลแพ็กเกจไม่สำเร็จ';
            $_SESSION['msg_type'] = 'danger';
        }
    }

    // 3. เพิ่มแพ็กเกจใหม่
    if (isset($_POST['add_pack'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $price = (float)$_POST['price'];
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail']));

        $sql_insert = "INSERT INTO pack (name, price, detail) VALUES ('$name', '$price', '$detail')";
        $insert_result = mysqli_query($conn, $sql_insert);

        if ($insert_result) {
            $_SESSION['msg'] = 'เพิ่มข้อมูลแพ็กเกจเสร็จแล้ว';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'เพิ่มข้อมูลแพ็กเกจไม่สำเร็จ';
            $_SESSION['msg_type'] = 'danger';
        }
    }

    header('location: pack_edit.php');
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

// ดึงรายการแพ็กเกจทั้งหมด
$sql_pack = "SELECT * FROM pack ORDER BY id ASC";
$pack_result = mysqli_query($conn, $sql_pack);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการแพ็กเกจ - Ecolyn Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">จัดการแพ็กเกจสมาชิก</h4>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPackModal">+ เพิ่มแพ็กเกจใหม่</button>
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
                            <th class="text-start">ชื่อแพ็กเกจ</th>
                            <th>ราคา (บาท)</th>
                            <th class="text-start">รายละเอียด</th>
                            <th style="min-width: 140px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($pack_item = mysqli_fetch_assoc($pack_result)) { 
                        $pid = (int)$pack_item['id'];
                    ?>
                        <tr>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($pack_item['name']); ?></td>
                            <td class="text-success fw-bold"><?php echo number_format($pack_item['price']); ?></td>
                            <td class="text-start"><small><?php echo nl2br(htmlspecialchars($pack_item['detail'])); ?></small></td>
                            <td class="text-nowrap">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#editModal_<?php echo $pid; ?>">แก้ไข</button>
                                    <form method="post" action="pack_edit.php" onsubmit="return confirm('ยืนยันการลบแพ็กเกจนี้?')">
                                        <input type="hidden" name="delete" value="<?php echo $pid; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- ป็อปอัป (Modal) แก้ไขแพ็กเกจ -->
                        <div class="modal fade" id="editModal_<?php echo $pid; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post" action="pack_edit.php">
                                        <input type="hidden" name="edit_id" value="<?php echo $pid; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">แก้ไขแพ็กเกจ</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-start">
                                            <div class="mb-3">
                                                <label class="form-label">ชื่อแพ็กเกจ</label>
                                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($pack_item['name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ราคา (บาท)</label>
                                                <input type="number" name="price" class="form-control" value="<?php echo (float)$pack_item['price']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">รายละเอียด</label>
                                                <textarea name="detail" class="form-control" rows="3"><?php echo htmlspecialchars($pack_item['detail']); ?></textarea>
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
                    <?php if (mysqli_num_rows($pack_result) == 0) { ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">ยังไม่มีรายการแพ็กเกจ</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ป็อปอัป (Modal) เพิ่มแพ็กเกจใหม่ -->
    <div class="modal fade" id="addPackModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="pack_edit.php">
                    <input type="hidden" name="add_pack" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">เพิ่มแพ็กเกจใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label">ชื่อแพ็กเกจ</label>
                            <input type="text" name="name" class="form-control" placeholder="กรอกชื่อแพ็กเกจ" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ราคา (บาท)</label>
                            <input type="number" name="price" class="form-control" placeholder="กรอกราคา" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียด</label>
                            <textarea name="detail" class="form-control" rows="3" placeholder="กรอกรายละเอียดสิทธิประโยชน์"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success">เพิ่มแพ็กเกจ</button>
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
