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
    // 1. ลบสินค้า
    if (isset($_POST['delete'])) {
        $del_id = (int)$_POST['delete'];
        $sql_del = "DELETE FROM product WHERE id_pro = $del_id";
        $del_result = mysqli_query($conn, $sql_del);

        if ($del_result) {
            $_SESSION['msg'] = 'ลบข้อมูลสินค้าสำเร็จ';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'ลบข้อมูลสินค้าไม่สำเร็จ';
            $_SESSION['msg_type'] = 'danger';
        }
    }

    // 2. แก้ไขข้อมูลสินค้า
    if (isset($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $shop_name = mysqli_real_escape_string($conn, trim($_POST['shop_name']));
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail']));
        $price = (float)$_POST['price'];
        $point_disc = (int)$_POST['point_disc'];
        $num = (int)$_POST['num'];
        $status = $_POST['status'];

        $sql_edit = "UPDATE product SET name = '$name', shop_name = '$shop_name', detail = '$detail', price = '$price', point_disc = '$point_disc', num = '$num', status = '$status' WHERE id_pro = $id";
        $edit_result = mysqli_query($conn, $sql_edit);

        // ตรวจสอบการเปลี่ยนรูปภาพสินค้า
        if (isset($_FILES['pic']) && $_FILES['pic']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
            $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

            if (in_array($ext, $allowed_ext)) {
                $filename = 'pro_' . $id . '.' . $ext;
                if (!is_dir('../img')) {
                    mkdir('../img', 0777, true);
                }
                move_uploaded_file($_FILES['pic']['tmp_name'], '../img/' . $filename);

                $sql_update_pic = "UPDATE product SET pic = '$filename' WHERE id_pro = $id";
                mysqli_query($conn, $sql_update_pic);
            }
        }

        if ($edit_result) {
            $_SESSION['msg'] = 'แก้ไขข้อมูลสินค้าสำเร็จ';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'แก้ไขข้อมูลสินค้าไม่สำเร็จ';
            $_SESSION['msg_type'] = 'danger';
        }
    }

    // 3. เพิ่มสินค้าใหม่
    if (isset($_POST['add_product'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $shop_name = mysqli_real_escape_string($conn, trim($_POST['shop_name']));
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail']));
        $price = (float)$_POST['price'];
        $point_disc = (int)$_POST['point_disc'];
        $num = (int)$_POST['num'];
        $status = 'active';

        $sql_insert = "INSERT INTO product (name, shop_name, detail, price, point_disc, num, pic, status, total) VALUES ('$name', '$shop_name', '$detail', '$price', '$point_disc', '$num', '', '$status', '')";
        $insert_result = mysqli_query($conn, $sql_insert);
        $new_id = mysqli_insert_id($conn);

        // ตรวจสอบการอัปโหลดรูปภาพสินค้า
        if (isset($_FILES['pic']) && $_FILES['pic']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
            $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

            if (in_array($ext, $allowed_ext)) {
                $filename = 'pro_' . $new_id . '.' . $ext;
                if (!is_dir('../img')) {
                    mkdir('../img', 0777, true);
                }
                move_uploaded_file($_FILES['pic']['tmp_name'], '../img/' . $filename);

                $sql_update_new_pic = "UPDATE product SET pic = '$filename' WHERE id_pro = $new_id";
                mysqli_query($conn, $sql_update_new_pic);
            }
        }

        if ($insert_result) {
            $_SESSION['msg'] = 'เพิ่มข้อมูลสินค้าสำเร็จ';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'เพิ่มข้อมูลสินค้าไม่สำเร็จ';
            $_SESSION['msg_type'] = 'danger';
        }
    }

    header('location: product_edit.php');
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

// ดึงรายการสินค้าทั้งหมด
$sql_products = "SELECT * FROM product ORDER BY id_pro DESC";
$pro_result = mysqli_query($conn, $sql_products);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - Ecolyn Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">จัดการสินค้าผลิตภัณฑ์รักษ์โลก</h4>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">+ เพิ่มสินค้าใหม่</button>
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
                            <th>รูปภาพ</th>
                            <th class="text-start">ชื่อสินค้า</th>
                            <th class="text-start">ชื่อร้านค้า</th>
                            <th>ราคา (บาท)</th>
                            <th>แต้มลดได้</th>
                            <th>คงเหลือ</th>
                            <th>สถานะ</th>
                            <th style="min-width: 140px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($p = mysqli_fetch_assoc($pro_result)) { 
                        $pid = (int)$p['id_pro'];
                        $is_active = ($p['status'] == 'active');
                    ?>
                        <tr>
                            <td>
                                <?php if ($p['pic'] != '') { ?>
                                    <img src="../img/<?php echo htmlspecialchars($p['pic']); ?>" style="width: 50px; height: 50px; object-fit: cover;" class="rounded border">
                                <?php } else { ?>
                                    <span class="text-muted small">-</span>
                                <?php } ?>
                            </td>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($p['name']); ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($p['shop_name']); ?></td>
                            <td class="text-success fw-bold"><?php echo number_format($p['price'], 2); ?></td>
                            <td><?php echo number_format($p['point_disc']); ?></td>
                            <td><?php echo (int)$p['num']; ?> ชิ้น</td>
                            <td>
                                <?php if ($is_active) { ?>
                                    <span class="badge bg-success">วางจำหน่าย</span>
                                <?php } else { ?>
                                    <span class="badge bg-danger">ปิดการขาย</span>
                                <?php } ?>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#editModal_<?php echo $pid; ?>">แก้ไข</button>
                                    <form method="post" action="product_edit.php" onsubmit="return confirm('ยืนยันการลบสินค้านี้?')">
                                        <input type="hidden" name="delete" value="<?php echo $pid; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- ป็อปอัป (Modal) สำหรับแก้ไขสินค้า -->
                        <div class="modal fade" id="editModal_<?php echo $pid; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post" action="product_edit.php" enctype="multipart/form-data">
                                        <input type="hidden" name="edit_id" value="<?php echo $pid; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">แก้ไขสินค้า</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-start">
                                            <div class="mb-3">
                                                <label class="form-label">ชื่อสินค้า</label>
                                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($p['name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ชื่อร้านค้า</label>
                                                <input type="text" name="shop_name" class="form-control" value="<?php echo htmlspecialchars($p['shop_name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">รายละเอียดสินค้า</label>
                                                <textarea name="detail" class="form-control" rows="3"><?php echo htmlspecialchars($p['detail']); ?></textarea>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <label class="form-label">ราคาสินค้า (บาท)</label>
                                                    <input type="number" step="0.01" name="price" class="form-control" value="<?php echo (float)$p['price']; ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">แต้มส่วนลดสูงสุด</label>
                                                    <input type="number" name="point_disc" class="form-control" value="<?php echo (int)$p['point_disc']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <label class="form-label">จำนวนสินค้าคงเหลือ</label>
                                                    <input type="number" name="num" class="form-control" value="<?php echo (int)$p['num']; ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">สถานะ</label>
                                                    <select name="status" class="form-select">
                                                        <option value="active" <?php if ($p['status'] == 'active') { echo 'selected'; } ?>>วางจำหน่าย</option>
                                                        <option value="inactive" <?php if ($p['status'] == 'inactive') { echo 'selected'; } ?>>ปิดการขาย</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">รูปภาพสินค้า (เลือกใหม่หากต้องการเปลี่ยน)</label>
                                                <input type="file" name="pic" class="form-control" accept="image/*">
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
                    <?php if (mysqli_num_rows($pro_result) == 0) { ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">ยังไม่มีรายการสินค้า</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ป็อปอัป (Modal) สำหรับเพิ่มสินค้าใหม่ -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="product_edit.php" enctype="multipart/form-data">
                    <input type="hidden" name="add_product" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">เพิ่มสินค้าใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label">ชื่อสินค้า</label>
                            <input type="text" name="name" class="form-control" placeholder="กรอกชื่อสินค้า" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ชื่อร้านค้า</label>
                            <input type="text" name="shop_name" class="form-control" placeholder="กรอกชื่อร้านค้า" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียดสินค้า</label>
                            <textarea name="detail" class="form-control" rows="3" placeholder="กรอกรายละเอียดสินค้า"></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">ราคาสินค้า (บาท)</label>
                                <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">แต้มส่วนลดสูงสุด</label>
                                <input type="number" name="point_disc" class="form-control" placeholder="0" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">จำนวนสินค้าคงเหลือ</label>
                                <input type="number" name="num" class="form-control" placeholder="0" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">สถานะ</label>
                                <select class="form-select" disabled>
                                    <option>วางจำหน่าย (เริ่มต้น)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รูปภาพสินค้า</label>
                            <input type="file" name="pic" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success">เพิ่มสินค้า</button>
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
