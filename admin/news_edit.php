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

// 1. ลบข่าวสาร
if (isset($_POST['delete'])) {
    $del_id = (int)$_POST['delete'];
    $sql_delete = "DELETE FROM news WHERE id = $del_id";
    mysqli_query($conn, $sql_delete);
    header('location: news_edit.php');
    exit();
}

// 2. แก้ไขข่าวสาร
if (isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $head = mysqli_real_escape_string($conn, trim($_POST['head']));
    $detail = mysqli_real_escape_string($conn, trim($_POST['detail']));

    $sql_update_news = "UPDATE news SET head = '$head', detail = '$detail' WHERE id = $id";
    mysqli_query($conn, $sql_update_news);

    // ตรวจสอบการอัปโหลดรูปภาพใหม่
    if (isset($_FILES['pic']) && $_FILES['pic']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        if (in_array($ext, $allowed_ext)) {
            $filename = 'news_' . $id . '.' . $ext;
            if (!is_dir('../img')) {
                mkdir('../img', 0777, true);
            }
            move_uploaded_file($_FILES['pic']['tmp_name'], '../img/' . $filename);

            $sql_update_pic = "UPDATE news SET pic = '$filename' WHERE id = $id";
            mysqli_query($conn, $sql_update_pic);
        }
    }

    header('location: news_edit.php');
    exit();
}

// 3. เพิ่มข่าวสารใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $head = mysqli_real_escape_string($conn, trim($_POST['head']));
    $detail = mysqli_real_escape_string($conn, trim($_POST['detail']));

    $sql_insert_news = "INSERT INTO news (head, detail, create_at) VALUES ('$head', '$detail', NOW())";
    mysqli_query($conn, $sql_insert_news);
    $new_id = mysqli_insert_id($conn);

    // ตรวจสอบการอัปโหลดรูปภาพข่าว
    if (isset($_FILES['pic']) && $_FILES['pic']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        if (in_array($ext, $allowed_ext)) {
            $filename = 'news_' . $new_id . '.' . $ext;
            if (!is_dir('../img')) {
                mkdir('../img', 0777, true);
            }
            move_uploaded_file($_FILES['pic']['tmp_name'], '../img/' . $filename);

            $sql_update_new_pic = "UPDATE news SET pic = '$filename' WHERE id = $new_id";
            mysqli_query($conn, $sql_update_new_pic);
        }
    }

    header('location: news_edit.php');
    exit();
}

// ดึงรายการข่าวสารทั้งหมด
$sql_all_news = "SELECT * FROM news ORDER BY id DESC";
$news_result = mysqli_query($conn, $sql_all_news);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข่าวสาร - Ecolyn Admin</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">จัดการข่าวสารและประชาสัมพันธ์</h4>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addNewsModal">+ เพิ่มข่าวสารใหม่</button>
        </div>

        <div class="card shadow-sm p-3">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-start">หัวข้อข่าว</th>
                            <th class="text-start">รายละเอียด</th>
                            <th>รูปภาพ</th>
                            <th>วันที่ลงข่าว</th>
                            <th style="min-width: 140px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($news_item = mysqli_fetch_assoc($news_result)) { 
                        $nid = (int)$news_item['id'];
                        $news_date_display = date('d/m/Y', strtotime($news_item['create_at']));
                    ?>
                        <tr>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($news_item['head']); ?></td>
                            <td class="text-start"><small><?php echo htmlspecialchars($news_item['detail']); ?></small></td>
                            <td>
                                <?php if ($news_item['pic'] != '') { ?>
                                    <img src="../img/<?php echo htmlspecialchars($news_item['pic']); ?>" style="width: 60px; height: 45px; object-fit: cover;" class="rounded border">
                                <?php } else { ?>
                                    <span class="text-muted small">ไม่มีรูป</span>
                                <?php } ?>
                            </td>
                            <td><?php echo $news_date_display; ?></td>
                            <td class="text-nowrap">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#editModal_<?php echo $nid; ?>">แก้ไข</button>
                                    <form method="post" action="news_edit.php" onsubmit="return confirm('ยืนยันการลบข่าวสารนี้?')">
                                        <input type="hidden" name="delete" value="<?php echo $nid; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- ป็อปอัป (Modal) สำหรับแก้ไขข่าวสาร -->
                        <div class="modal fade" id="editModal_<?php echo $nid; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post" action="news_edit.php" enctype="multipart/form-data">
                                        <input type="hidden" name="edit_id" value="<?php echo $nid; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">แก้ไขข่าวสาร</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-start">
                                            <div class="mb-3">
                                                <label class="form-label">หัวข้อข่าว</label>
                                                <input type="text" name="head" class="form-control" value="<?php echo htmlspecialchars($news_item['head']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">รายละเอียด</label>
                                                <textarea name="detail" class="form-control" rows="3"><?php echo htmlspecialchars($news_item['detail']); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">รูปภาพข่าว (เลือกใหม่หากต้องการเปลี่ยน)</label>
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
                    <?php if (mysqli_num_rows($news_result) == 0) { ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">ยังไม่มีรายการข่าวสาร</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ป็อปอัป (Modal) เพิ่มข่าวสารใหม่ -->
    <div class="modal fade" id="addNewsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="news_edit.php" enctype="multipart/form-data">
                    <input type="hidden" name="add_news" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">เพิ่มข่าวสารใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">หัวข้อข่าว</label>
                            <input type="text" name="head" class="form-control" placeholder="กรอกหัวข้อข่าว" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียด</label>
                            <textarea name="detail" class="form-control" rows="3" placeholder="กรอกรายละเอียดข่าวสาร"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รูปภาพข่าว</label>
                            <input type="file" name="pic" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success">เพิ่มข่าวสาร</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include '../footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
