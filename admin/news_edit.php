<?php 
session_start();
include '../config.php';
$company_id = (int)($_SESSION['company_id'] ?? 0);
$company = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$company_id"));

if (!$company || $company['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

if(isset($_POST['delete'])){
    $del_id = $_POST['delete'];
    mysqli_query($conn, "DELETE FROM news WHERE id=$del_id");
    header('location: news_edit.php');
    exit();
}

if(isset($_POST['edit_id'])){
    $id = $_POST['edit_id'];
    $head = $_POST['head'];
    $detail = $_POST['detail'];
    mysqli_query($conn, "update news set head='$head', detail='$detail' where id=$id");

    if($_FILES['pic']['error'] === UPLOAD_ERR_OK){
        $ext = "." . strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
        if($ext == ".jpg" or $ext == ".png" or $ext == ".gif"){
            $filename = $id . $ext;
            move_uploaded_file($_FILES['pic']['tmp_name'], "news/$filename");
            mysqli_query($conn, "update news set pic='$filename' where id=$id");
        }
    }
    header('location: news_edit.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = $_POST['head'];
    $detail = $_POST['detail'];
    mysqli_query($conn, "insert into news(head,detail,create_at) values('$name','$detail',now())");
    $id_news = mysqli_insert_id($conn);

    if($_FILES['pic']['error'] === UPLOAD_ERR_OK){
        $ext = "." . strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
        if($ext == ".jpg" or $ext == ".png" or $ext == ".gif"){
            $filename = $id_news . $ext;
            move_uploaded_file($_FILES['pic']['tmp_name'], "news/$filename");
            mysqli_query($conn, "update news set pic='$filename' where id=$id_news");
        }
    }
    header('location: news_edit.php');
    exit();
}

$news_result = mysqli_query($conn, "select * from news");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>news</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css" >
</head>
<body>
    <?php include '../navbar.php'; ?>
     <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold">จัดการข่าวสาร</h4>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ เพิ่มข่าวสาร</button>
        </div>

        <div class="card shadow-sm p-3">
            <table class="table table-hover table-bordered align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>หัวข้อข่าว</th>
                        <th>รายละเอียด</th>
                        <th>รูปข่าว</th>
                        <th>วันที่ลงข่าว</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($news_item = mysqli_fetch_assoc($news_result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($news_item['head']) ?></td>
                        <td><?= htmlspecialchars($news_item['detail']) ?></td>
                        <td>
                            <?php if($news_item['pic'] != ''): ?>
                                <img src="../img/<?= $news_item['pic'] ?>" width="60">
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($news_item['create_at'])) ?></td>
                        <td class="text-nowrap">
                            <div class="d-flex justify-content-center gap-1">
                                 <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $news_item['id'] ?>">แก้ไข</button>
                                <form method="post">
                                    <input type="hidden" name="delete" value="<?= $news_item['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <div class="modal fade" id="editModal<?= $news_item['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="edit_id" value="<?= $news_item['id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">แก้ไขข่าวสาร</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">หัวข้อข่าว</label>
                                            <input type="text" name="head" class="form-control" value="<?= htmlspecialchars($news_item['head']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">รายละเอียด</label>
                                            <textarea name="detail" class="form-control" rows="3"><?= htmlspecialchars($news_item['detail']) ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">รูปข่าว (เลือกใหม่ถ้าต้องการเปลี่ยน)</label>
                                            <input type="file" name="pic" class="form-control">
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

                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">เพิ่มข่าวสารใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">หัวข้อข่าว</label>
                        <input type="text" name="head" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รายละเอียด</label>
                        <textarea name="detail" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รูปข่าว</label>
                        <input type="file" name="pic" class="form-control">
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

<script src="../js/bootstrap.bundle.min.js"></script>

</body>
</html>