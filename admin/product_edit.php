<?php
    session_start();
    include '../config.php';
    $company_id = (int)($_SESSION['company_id'] ?? 0);
    $company = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$company_id"));

    if (!$company || $company['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if(isset($_POST['delete'])){
            $del_id = $_POST['delete'];
            $del="delete from product where id=$del_id";
            $del_result=mysqli_query($conn, $del);
            if($del_result){
                $_SESSION['msg'] ='ลบข้อมูลสินค้าสำเร็จ';
                $_SESSION['msg_type'] ='success';
            } else{
                $_SESSION['msg'] ='ลบข้อมูลสินค้าไม่สำเร็จ';
                $_SESSION['msg_type'] ='danger';
            }
        }
        

        // 2. แก้ไข
        if(isset($_POST['edit_id'])){
            $id = $_POST['edit_id'];
            $name = $_POST['name'];
            $shop_name = $_POST['shop_name'];
            $detail = $_POST['detail'];
            $price = $_POST['price'];
            $point_disc = $_POST['point_disc'];
            $num = $_POST['num'];
            $status = $_POST['status'];
            $edit="update product set name='$name', shop_name='$shop_name', detail='$detail', price='$price', point_disc='$point_disc', num='$num', status='$status' where id=$id";
            $edit_result=mysqli_query($conn,$edit );
            if($_FILES['pic']['error'] === UPLOAD_ERR_OK){
                $ext = "." . strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
                if($ext == ".jpg" or $ext == ".png" or $ext == ".gif"){
                    $filename = $id . $ext;
                    move_uploaded_file($_FILES['pic']['tmp_name'], "../img/$filename");
                    mysqli_query($conn, "update product set pic='$filename' where id=$id");
                }else{
                    $_SESSION['msg'] ='ไฟล์รูปภาพไม่ถูกต้อง';
                    $_SESSION['msg_type'] ='danger';
                }
            }else{
                $_SESSION['msg'] ='แก้ไขรูปภาพไม่สำเร็จ';
                $_SESSION['msg_type'] ='danger';
            }
            if($edit_result){
                $_SESSION['msg'] ='แก้ไขข้อมูลสินค้าสำเร็จ';
                $_SESSION['msg_type'] ='success';
            } else{
                $_SESSION['msg'] ='แก้ไขข้อมูลสินค้าไม่สำเร็จ';
                $_SESSION['msg_type'] ='danger';
            }
        }

        // 3. เพิ่ม
        if((!isset($_POST['delete']) && !isset($_POST['edit_id']))){
            $name = $_POST['name'];
            $shop_name = $_POST['shop_name'];
            $detail = $_POST['detail'];
            $price = $_POST['price'];
            $point_disc = $_POST['point_disc'];
            $num = $_POST['num'];
            $status = 'active';
            $sql="insert into product values(null,'$name','$shop_name','$detail','$price','$point_disc','$num','','$status','')";
            $result=mysqli_query($conn,$sql);
            $id = mysqli_insert_id($conn);

            if($_FILES['pic']['error'] === UPLOAD_ERR_OK){
                $ext = "." . strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
                if($ext == ".jpg" or $ext == ".png" or $ext == ".gif"){
                    $filename = $id . $ext;
                    move_uploaded_file($_FILES['pic']['tmp_name'], "../img/$filename");
                    mysqli_query($conn, "update product set pic='$filename' where id=$id");
                }else{
                    $_SESSION['msg'] ='ไฟล์รูปภาพไม่ถูกต้อง';
                    $_SESSION['msg_type'] ='danger';
                }
            }else{
                $_SESSION['msg'] ='เพิ่มรูปภาพไม่สำเร็จ';
                $_SESSION['msg_type'] ='danger';
            }
            if($result){
                $_SESSION['msg'] ='เพิ่มข้อมูลสินค้าสำเร็จ';
                $_SESSION['msg_type'] ='success';
            } else{
                $_SESSION['msg'] ='เพิ่มข้อมูลสินค้าไม่สำเร็จ';
                $_SESSION['msg_type'] ='danger';
            }
        }
        header('location: product_edit.php');
        exit();

    }

    $msg=$_SESSION['msg'] ?? '';
    $msg_type=$_SESSION['msg_type'] ?? 'info';
    unset($_SESSION['msg'],$_SESSION['msg_type']);

    $pro_result = mysqli_query($conn, "select * from product");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้า</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
<?php include '../navbar.php'; ?>
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">จัดการสินค้า</h4>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ เพิ่มสินค้า</button>
    </div>

    <?php if ($msg!==''): ?>
        <div class="alert alert-<?= $msg_type ?> py-2 small text-center">
            <h6><?= htmlspecialchars($msg) ?></h6>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm p-3">
        <table class="table table-hover table-bordered align-middle">
            <thead class="bg-light">
                <tr class="text-center">
                    <th>รูปภาพสินค้า</th>
                    <th>ชื่อสินค้า</th>
                    <th>ชื่อร้านค้า</th>
                    <th>ราคาสินค้า</th>
                    <th>แต้มส่วนลด</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php while($p = mysqli_fetch_assoc($pro_result)): ?>
                <tr class="text-center">
                    <td>
                        <?php if($p['pic'] != ''): ?>
                            <img src="../img/<?= $p['pic'] ?>" width="44" height="44">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['shop_name']) ?></td>
                    <td><?= $p['price'] ?></td>
                    <td><?= $p['point_disc'] ?></td>
                    <td>
                        <?php if($p['status'] == 'active'): ?>
                            <span class="badge bg-success">วางจำหน่าย</span>
                        <?php else: ?>
                            <span class="badge bg-danger">ปิดการขาย</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id_pro'] ?>">แก้ไข</button>
                            <form method="post">
                                <input type="hidden" name="delete" value="<?= $p['id_pro'] ?>">
                                <button class="btn btn-sm btn-outline-danger">ลบ</button>
                            </form>
                        </div>
                    </td>
                </tr>

                <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="edit_id" value="<?= $p['id'] ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">แก้ไขสินค้า</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">ชื่อสินค้า</label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">ชื่อร้านค้า</label>
                                        <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($p['shop_name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">รายละเอียด</label>
                                        <textarea name="detail" class="form-control" rows="3"><?= htmlspecialchars($p['detail']) ?></textarea>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label">ราคาสินค้า</label>
                                            <input type="number" name="price" class="form-control" value="<?= $p['price'] ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">แต้มส่วนลด</label>
                                            <input type="number" name="point_disc" class="form-control" value="<?= $p['point_disc'] ?>" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label">จำนวนสินค้า</label>
                                            <input type="number" name="num" class="form-control" value="<?= $p['num'] ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">สถานะ</label>
                                            <select name="status" class="form-select">
                                                <option value="active" <?= $p['status']=='active' ? 'selected' : '' ?>>วางจำหน่าย</option>
                                                <option value="inactive" <?= $p['status']=='inactive' ? 'selected' : '' ?>>ปิดการขาย</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">รูปภาพสินค้า (เลือกใหม่ถ้าต้องการเปลี่ยน)</label>
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
                    <h5 class="modal-title fw-bold">เพิ่มสินค้าใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อสินค้า</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อร้านค้า</label>
                        <input type="text" name="shop_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รายละเอียด</label>
                        <textarea name="detail" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">ราคาสินค้า</label>
                            <input type="number" name="price" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">แต้มส่วนลด</label>
                            <input type="number" name="point_disc" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">จำนวนสินค้า</label>
                            <input type="number" name="num" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">สถานะ</label>
                            <select class="form-select" disabled>
                                <option>วางจำหน่าย</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รูปภาพสินค้า</label>
                        <input type="file" name="pic" class="form-control">
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

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>