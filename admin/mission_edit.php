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
            $del="delete from mission where id=$del_id";
            $del_result=mysqli_query($conn,$del );
            if($del_result){
                $_SESSION['msg'] ='ลบข้อมูลภารกิจสำเร็จ';
                $_SESSION['msg_type'] ='success';
            }else{
                $_SESSION['msg'] ='ลบข้อมูลภารกิจไม่สำเร็จ';
                $_SESSION['msg_type'] ='danger';
            }
            
        }

        // 2. แก้ไข
        if(isset($_POST['edit_id'])){
            $id = $_POST['edit_id'];
            $title = $_POST['title'];
            $detail = $_POST['detail'];
            $descrip = $_POST['descrip'];
            $point_reward = $_POST['point_reward'];
            $co2e_save = $_POST['co2e_save'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $status = $_POST['status'];
            $edit="update mission set title='$title', detail='$detail', descrip='$descrip', point_reward='$point_reward', co2e_save='$co2e_save', start_date='$start_date', end_date='$end_date', status='$status' where id=$id";
            $edit_result=mysqli_query($conn,$edit);
            if($edit_result){
                $_SESSION['msg'] ='แก้ไขข้อมูลภารกิจสำเร็จ';
                $_SESSION['msg_type'] ='success';
            }else{
                $_SESSION['msg'] ='แก้ไขข้อมูลภารกิจไม่สำเร็จ';
                $_SESSION['msg_type'] ='danger';
            }
        }

        // 3. เพิ่ม
        if(!isset($_POST['delete']) && !isset($_POST['edit_id'])){
            $title = $_POST['title'];
            $detail = $_POST['detail'];
            $descrip = $_POST['descrip'];
            $point_reward = $_POST['point_reward'];
            $co2e_save = $_POST['co2e_save'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $status = $_POST['status'];
            $sql="insert into mission values(null,'$title','$detail','$descrip','$status','$point_reward','$co2e_save','$start_date','$end_date',now())";
            $result=mysqli_query($conn,$sql );
            if($result){
                $_SESSION['msg'] ='เพิ่มข้อมูลภารกิจสำเร็จ';
                $_SESSION['msg_type'] ='success';
            }else{
                $_SESSION['msg'] ='เพิ่มข้อมูลภารกิจไม่สำเร็จ';
                $_SESSION['msg_type'] ='danger';
            }
        }
        header('location: mission_edit.php');
        exit();
    }

    $msg=$_SESSION['msg'] ?? '';
    $msg_type=$_SESSION['msg_type'] ?? 'info';
    unset($_SESSION['msg'],$_SESSION['msg_type']);

    $mission="select * from mission order by id desc";
    $mission_result = mysqli_query($conn,$mission );
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ภารกิจ</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include '../navbar.php'; ?>
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">จัดการภารกิจลดคาร์บอน</h4>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ เพิ่มภารกิจ</button>
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
                    <th>ชื่อภารกิจ</th>
                    <th>รายละเอียดโดยย่อ</th>
                    <th>แต้ม</th>
                    <th>CO₂ (kg)</th>
                    <th>วันที่เริ่ม</th>
                    <th>วันที่สิ้นสุด</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php while($m = mysqli_fetch_assoc($mission_result)): ?>
                <tr class="text-center">
                    <td class="text-start"><?= htmlspecialchars($m['title']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($m['detail']) ?></td>
                    <td><?= $m['point_reward'] ?></td>
                    <td><?= $m['co2e_save'] ?></td>
                    <td><?= date('d/m/Y', strtotime($m['start_date'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($m['end_date'])) ?></td>
                    <td>
                        <?php if($m['status'] == 'active'): ?>
                            <span class="badge bg-success">เปิด</span>
                        <?php else: ?>
                            <span class="badge bg-danger">ปิด</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $m['id'] ?>">แก้ไข</button>
                            <form method="post" onsubmit="return confirm('ลบภารกิจนี้?')">
                                <input type="hidden" name="delete" value="<?= $m['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">ลบ</button>
                            </form>
                        </div>
                    </td>
                </tr>

                <div class="modal fade" id="editModal<?= $m['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post">
                                <input type="hidden" name="edit_id" value="<?= $m['id'] ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">แก้ไขภารกิจ</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">ชื่อภารกิจ</label>
                                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($m['title']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">รายละเอียดโดยย่อ</label>
                                        <input type="text" name="detail" class="form-control" value="<?= htmlspecialchars($m['descrip']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">รายละเอียดภารกิจ/เงื่อนไข</label>
                                        <textarea name="descrip" class="form-control" rows="3"><?= htmlspecialchars($m['detail']) ?></textarea>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label">แต้มรางวัล</label>
                                            <input type="number" name="point_reward" class="form-control" value="<?= $m['point_reward'] ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">CO₂ ที่ลดได้ (kg)</label>
                                            <input type="number" step="0.01" name="co2e_save" class="form-control" value="<?= $m['co2e_save'] ?>" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label">วันที่เริ่ม</label>
                                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d', strtotime($m['start_date'])) ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">วันที่สิ้นสุด</label>
                                            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime($m['end_date'])) ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">สถานะ</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?= $m['status']=='active' ? 'selected' : '' ?>>เปิดให้ทำภารกิจ (active)</option>
                                            <option value="inactive" <?= $m['status']=='inactive' ? 'selected' : '' ?>>ปิด (inactive)</option>
                                        </select>
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
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">เพิ่มภารกิจใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อภารกิจ</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รายละเอียด</label>
                        <input type="text" name="detail" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รายละเอียดภารกิจ/เงื่อนไข</label>
                        <textarea name="descrip" class="form-control" rows="3"></textarea>
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
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">วันที่สิ้นสุด</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-select" disabled>
                            <option value="active">เปิดให้ทำภารกิจ (active)</option>
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

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>