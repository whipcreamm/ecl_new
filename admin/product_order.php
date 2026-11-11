<?php
session_start();
include '../config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:../login.php');
    exit();
}
$cid = (int)$_SESSION['company_id'];

$me = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$cid"));
if (!$me || $me['role_id'] != 1) {
    header('location:../login.php');
    exit();
}

// สถานะคำสั่งซื้อ: ค่าในฐานข้อมูล => [ชื่อที่แสดง, สีป้าย]
$status_list = [
    'pending'  => ['รอดำเนินการ', 'warning text-dark'],
    'paid'     => ['ชำระเงินแล้ว', 'info text-dark'],
    'ship'     => ['จัดส่งแล้ว', 'primary'],
    'complete' => ['สำเร็จ', 'success'],
    'cancel'   => ['ยกเลิก', 'danger']
];

if(isset($_POST['order_id'])){
    $oid = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $od = mysqli_fetch_assoc(mysqli_query($conn, "select * from product_order where id=$oid"));
    if($od && isset($status_list[$status])){
        mysqli_query($conn, "update product_order set status='$status' where id=$oid");

        $to = (int)$od['company_id'];
        $text = "คำสั่งซื้อ {$od['order_no']} เปลี่ยนสถานะเป็น {$status_list[$status][0]}";
        mysqli_query($conn, "insert into noti(recip_id,message,type,link) values ($to,'$text','order','product.php')");

        $_SESSION['flash'] = ['success', 'เปลี่ยนสถานะคำสั่งซื้อสำเร็จ'];
    }else{
        $_SESSION['flash'] = ['danger', 'เปลี่ยนสถานะคำสั่งซื้อไม่สำเร็จ'];
    }
    header('location: product_order.php');
    exit();
}

$alert = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// เก็บสินค้าไว้ใน array ตาม id_pro
$prs = [];
$r = mysqli_query($conn, "select * from product");
while($x = mysqli_fetch_assoc($r)) $prs[$x['id_pro']] = $x;

$order_result = mysqli_query($conn, "select * from product_order order by id desc");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>product order</title>
</head>
<body class="bg-light">
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">จัดการคำสั่งซื้อ</h3>

        <?php if($alert): ?>
            <div class="alert alert-<?= $alert[0] ?> p-2 text-center mt-3"><?= $alert[1] ?></div>
        <?php endif; ?>

        <div class="card shadow-sm p-3 mt-3">
            <table class="table table-hover table-bordered align-middle">
                <thead class="bg-light">
                    <tr class="text-center">
                        <th>เลขที่สั่งซื้อ</th>
                        <th>สินค้า</th>
                        <th>จำนวน</th>
                        <th>ราคาสุทธิ (บาท)</th>
                        <th>ผู้สั่งซื้อ</th>
                        <th>ที่อยู่</th>
                        <th>ชำระเงิน</th>
                        <th>วันที่</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($o = mysqli_fetch_assoc($order_result)): ?>
                    <?php $pr = $prs[$o['product_id']] ?? null; ?>
                    <tr class="text-center">
                        <td><?= $o['order_no'] ?></td>
                        <td class="text-start"><?= $pr['name'] ?? '-' ?></td>
                        <td><?= $o['qty'] ?></td>
                        <td class="text-success fw-bold"><?= number_format($o['final_price'], 2) ?></td>
                        <td class="text-start"><?= $o['buy_name'] ?><br><small class="text-muted"><?= $o['buy_tel'] ?></small></td>
                        <td class="text-start"><?= $o['ship_addr'] ?></td>
                        <td><?= $o['pay_method'] ?></td>
                        <td><?= date('d/m/Y', strtotime($o['create_at'])) ?></td>
                        <td>
                            <span class="badge bg-<?= $status_list[$o['status']][1] ?? 'secondary' ?>"><?= $status_list[$o['status']][0] ?? $o['status'] ?></span>
                        </td>
                        <td>
                            <form method="post" class="d-flex gap-1">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <?php foreach($status_list as $key => $s): ?>
                                        <option value="<?= $key ?>" <?= $o['status'] == $key ? 'selected' : '' ?>><?= $s[0] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-success">บันทึก</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if(mysqli_num_rows($order_result) == 0): ?>
            <p class="text-muted text-center mt-4">ยังไม่มีคำสั่งซื้อ</p>
        <?php endif; ?>
    </div>
<?php include '../footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>