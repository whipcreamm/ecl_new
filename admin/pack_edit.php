<?php 
session_start();
include '../config.php';
$company_id = (int)($_SESSION['company_id'] ?? 0);
$company = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$company_id"));

if (!$company || $company['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}
    

    if($_SERVER['REQUEST_METHOD']=='POST'){
        if(isset($_POST['delete'])){
        $del_id = $_POST['delete'];
        $del="delete from pack where id=$del_id";
        $del_result=mysqli_query($conn,$del);
            if($del_result){
                $_SESSION['msg']='ลบข้อมูลแพ็กเกจเสร็จแล้ว';
                $_SESSION['msg_type']='success';
            }else{
                $_SESSION['msg']='ลบข้อมูลแพ็กเกจไม่สำเร็จ';
                $_SESSION['msg_type']='danger';
            }
        }

        if(isset($_POST['edit_id'])){
            $id = $_POST['edit_id'];
            $name = $_POST['name'];
            $price = $_POST['price'];
            $detail = $_POST['detail'];
            $edit="update pack set name='$name', price='$price', detail='$detail' where id=$id";
            $edit_result=mysqli_query($conn,$edit);
            if($edit_result){
                $_SESSION['msg']='แก้ไขข้อมูลแพ็กเกจเสร็จแล้ว';
                $_SESSION['msg_type']='success';
            }else{
                $_SESSION['msg']='แก้ไขข้อมูลแพ็กเกจไม่สำเร็จ';
                $_SESSION['msg_type']='danger';
            }
        }

        if(!isset($_POST['delete']) && !isset($_POST['edit_id'])){
            $name = $_POST['name'];
            $price = $_POST['price'];
            $detail = $_POST['detail'];
            $sql="insert into pack values(null,'$name','$price','$detail')";
            $result=mysqli_query($conn,$sql);
            if($result){
                $_SESSION['msg']='เพิ่มข้อมูลแพ็กเกจเสร็จแล้ว';
                $_SESSION['msg_type']='success';
            }else{
                $_SESSION['msg']='เพิ่มข้อมูลแพ็กเกจไม่สำเร็จ';
                $_SESSION['msg_type']='danger';
            }
        }
        header('location: pack_edit.php');
        exit();
    }
    

    $msg = $_SESSION['msg'] ?? '';
    $msg_type = $_SESSION['msg_type'] ?? 'info';
    unset($_SESSION['msg'], $_SESSION['msg_type']);

    $pack="select * from pack";
    $pack_result = mysqli_query($conn,$pack);
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>package</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css" >
</head>
<body>
    <?php include '../navbar.php'; ?>
   <div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">จัดการแพ็กเกจ</h4>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ เพิ่มแพ็กเกจ</button>
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
                    <th>แพ็กเกจ</th>
                    <th>ราคา</th>
                    <th>รายละเอียด</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($pack_result)>0): ?>
                    <?php while($pack_item = mysqli_fetch_assoc($pack_result)): ?>
                        <tr class="text-center">
                            <td><?= $pack_item['name'] ?></td>
                            <td><?= $pack_item['price'] ?></td>
                            <td><?= $pack_item['detail'] ?></td>
                            <td class="text-nowrap">
                                <div class="d-flex justify-content-center gap-1">
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $pack_item['id'] ?>">แก้ไข</button>
                                    <form method="post">
                                        <input type="hidden" name="delete" value="<?= $pack_item['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="editModal<?= $pack_item['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <input type="hidden" name="edit_id" value="<?= $pack_item['id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">แก้ไขแพ็กเกจ</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">ชื่อแพ็กเกจ</label>
                                                <input type="text" name="name" class="form-control" value="<?= $pack_item['name'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ราคา</label>
                                                <input type="number" name="price" class="form-control" value="<?= $pack_item['price'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">รายละเอียด</label>
                                                <textarea name="detail" class="form-control" rows="3"><?= $pack_item['detail'] ?></textarea>
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>



<script src="../js/bootstrap.bundle.min.js"></script>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">เพิ่มแพ็กเกจใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อแพ็กเกจ</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ราคา</label>
                        <input type="number" name="price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รายละเอียด</label>
                        <textarea name="detail" class="form-control" rows="3"></textarea>
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


</body>
</html>