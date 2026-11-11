<?php
session_start();
include 'config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:login.php');
    exit();
}
$cid = (int)$_SESSION['company_id'];
$me = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$cid"));

$active_tab = $_GET['tab'] ?? 'products';

// แต้มคงเหลือ
$points = 0;
$r = mysqli_query($conn, "select * from eco_point_ledger where company_id=$cid");
while($p = mysqli_fetch_assoc($r)) $points += $p['point'];

// เก็บสินค้าไว้ใน array ตาม id_pro
$prs = [];
$r = mysqli_query($conn, "select * from product");
while($x = mysqli_fetch_assoc($r)) $prs[$x['id_pro']] = $x;

// ---------- สั่งซื้อสินค้า ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product = $prs[(int)$_POST['product_id']] ?? null;
    $qty = max(1, (int)$_POST['qty']);
    $buy_name = mysqli_real_escape_string($conn, trim($_POST['buy_name']));
    $buy_tel = mysqli_real_escape_string($conn, trim($_POST['buy_tel']));
    $ship_addr = mysqli_real_escape_string($conn, trim($_POST['ship_addr']));
    $pay_method = $_POST['pay_method'] == 'transfer' ? 'transfer' : 'cod';

    if (!$product || $product['status'] != 'active') {
        $_SESSION['msg'] = ['danger', 'ไม่พบสินค้า หรือปิดการขายแล้ว'];
    } elseif ($product['num'] < $qty) {
        $_SESSION['msg'] = ['warning', 'สินค้าไม่พอ (เหลือ ' . $product['num'] . ' ชิ้น)'];
    } elseif ($buy_name == '' || $buy_tel == '' || $ship_addr == '') {
        $_SESSION['msg'] = ['warning', 'กรุณากรอกข้อมูลผู้รับให้ครบถ้วน'];
    } else {
        $total = (int)$product['price'] * $qty;
        $max_disc = (int)$product['point_disc'] * $qty;
        $input = max(0, (int)$_POST['points_used']);
        $use = min($input, $max_disc, $total, $points);   // แต้มที่ใช้จริง
        $final = $total - $use;
        $order_no = 'ORD' . date('Ymd') . rand(1000, 9999);
        $pid = (int)$product['id_pro'];

        mysqli_query($conn, "insert into product_order(order_no,company_id,product_id,qty,final_price,buy_name,buy_tel,ship_addr,pay_method,status,create_at) values('$order_no',$cid,$pid,$qty,'$final','$buy_name','$buy_tel','$ship_addr','$pay_method','pending',now())");
        mysqli_query($conn, "update product set num=num-$qty where id_pro=$pid");

        if ($use > 0) {
            $reason = mysqli_real_escape_string($conn, "ใช้แต้มซื้อ {$product['name']} ($order_no)");
            mysqli_query($conn, "insert into eco_point_ledger(company_id,point,reason) values($cid,-$use,'$reason')");
        }

        // แจ้งแอดมินทุกคน
        $text = mysqli_real_escape_string($conn, "{$me['name']} สั่งซื้อสินค้าใหม่ เลขที่ $order_no");
        $admins = mysqli_query($conn, "select * from company where role_id=1");
        while($ad = mysqli_fetch_assoc($admins)){
            mysqli_query($conn, "insert into noti(recip_id,message,type,link) values({$ad['id']},'$text','order','admin/product_order.php')");
        }

        $_SESSION['msg'] = ['success', "สั่งซื้อเรียบร้อย! รหัสคำสั่งซื้อ: <strong>$order_no</strong>"];
        header('location: product.php?tab=orders');
        exit();
    }
    header('location: product.php');
    exit();
}

$msg = $_SESSION['msg'] ?? null;
unset($_SESSION['msg']);

$q = trim($_GET['q'] ?? '');
$kw = mysqli_real_escape_string($conn, $q);
$where = $q != '' ? "and (name like '%$kw%' or detail like '%$kw%')" : '';
$products_query = mysqli_query($conn, "select * from product where status='active' $where order by id_pro desc");
$orders_query = mysqli_query($conn, "select * from product_order where company_id=$cid order by id desc");

$status_map = [
    'pending'  => ['warning text-dark', 'รอเตรียมจัดส่ง'],
    'paid'     => ['info text-dark',    'ชำระเงินแล้ว'],
    'ship'     => ['primary',           'จัดส่งแล้ว'],
    'complete' => ['success',           'สำเร็จ'],
    'cancel'   => ['danger',            'ยกเลิก'],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านค้า</title>
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mt-3">
        <h3 class="fw-bold">ร้านค้าผลิตภัณฑ์รักษ์โลก</h3>
        <span class="badge bg-success fs-6">แต้มของคุณ <?= number_format($points) ?></span>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $msg[0] ?> p-2 text-center mt-3"><?= $msg[1] ?></div>
    <?php endif; ?>

    <!-- แท็บ + ค้นหา -->
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'products' ? 'active bg-success' : 'text-dark' ?>" href="?tab=products">สินค้าทั้งหมด</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'orders' ? 'active bg-success' : 'text-dark' ?>" href="?tab=orders">คำสั่งซื้อของฉัน (<?= mysqli_num_rows($orders_query) ?>)</a>
            </li>
        </ul>
        <?php if ($active_tab === 'products'): ?>
            <form class="d-flex gap-2" method="get">
                <input type="text" name="q" class="form-control" placeholder="ค้นหาสินค้า..." value="<?= htmlspecialchars($q) ?>">
                <button class="btn btn-success">ค้นหา</button>
                <?php if ($q != ''): ?><a href="product.php" class="btn btn-outline-secondary">ล้าง</a><?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

<?php if ($active_tab === 'products'): ?>
    <div class="row">
        <?php while($p = mysqli_fetch_assoc($products_query)): ?>
            <div class="col-md-4 mt-3">
                <div class="card card-white shadow-sm d-flex flex-column p-2 h-100" <?= $p['num'] > 0 ? 'data-bs-toggle="modal" data-bs-target="#buy' . $p['id_pro'] . '"' : '' ?>>
                    <?php if($p['pic'] != ''): ?>
                        <img src="img/<?= $p['pic'] ?>" class="card-img-top border" style="height:200px; object-fit:cover;">
                    <?php else: ?>
                        <div class="bg-secondary text-white d-flex justify-content-center align-items-center" style="height:200px;">ไม่มีรูปภาพ</div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="fw-bold"><?= htmlspecialchars($p['name']) ?></h5>
                            <?php if($p['num'] > 0): ?>
                                <span class="badge bg-info">เหลือ <?= $p['num'] ?> ชิ้น</span>
                            <?php else: ?>
                                <span class="badge bg-danger">สินค้าหมด</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($p['detail']) ?></p>
                        <div class="d-flex justify-content-between align-items-end mt-5">
                            <div>
                                <div><b class="text-success"><?= number_format($p['price'], 2) ?></b> บาท</div>
                                <span class="border-2 text-muted text-dark">ลดได้ <?= number_format($p['point_disc']) ?> แต้ม</span>
                            </div>
                            <button type="button" class="btn btn-success" <?= $p['num'] > 0 ? '' : 'disabled' ?>>สั่งซื้อสินค้า</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="buy<?= $p['id_pro'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form method="post">
                            <input type="hidden" name="product_id" value="<?= $p['id_pro'] ?>">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold"><?= htmlspecialchars($p['name']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php if($p['pic'] != ''): ?>
                                    <img src="img/<?= $p['pic'] ?>" class="w-100 mb-3" style="max-height:250px; object-fit:cover;">
                                <?php endif; ?>
                                <p><?= htmlspecialchars($p['detail']) ?></p>
                                <p class="mb-3"><b class="text-success">฿<?= number_format($p['price'], 2) ?></b>
                                    <span class="badge bg-success">ลดได้สูงสุด <?= number_format($p['point_disc']) ?> แต้ม/ชิ้น</span></p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">จำนวน (เหลือ <?= (int)$p['num'] ?> ชิ้น)</label>
                                        <input type="number" name="qty" class="form-control" value="1" min="1" max="<?= (int)$p['num'] ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ใช้แต้มลดราคา (คุณมี <?= number_format($points) ?> แต้ม)</label>
                                        <input type="number" name="points_used" class="form-control" value="0" min="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ชื่อผู้รับ</label>
                                        <input type="text" name="buy_name" class="form-control" required value="<?= htmlspecialchars($me['name']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">เบอร์โทรศัพท์</label>
                                        <input type="text" name="buy_tel" class="form-control" required placeholder="08xxxxxxxx">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">ที่อยู่จัดส่ง</label>
                                        <textarea name="ship_addr" class="form-control" rows="2" required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">วิธีชำระเงิน</label>
                                        <select name="pay_method" class="form-select">
                                            <option value="cod">เก็บเงินปลายทาง (COD)</option>
                                            <option value="transfer">โอนเงิน</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-success" onclick="return confirm('ยืนยันการสั่งซื้อ?')">ยืนยันสั่งซื้อ</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if (mysqli_num_rows($products_query) == 0): ?>
        <p class="text-muted text-center mt-4">ไม่พบรายการสินค้า</p>
    <?php endif; ?>

<?php else: ?>
    <div class="card shadow-sm p-3 mt-3">
        <table class="table table-hover table-bordered align-middle mb-0">
            <thead class="bg-light">
                <tr class="text-center">
                    <th>รหัสคำสั่งซื้อ</th>
                    <th>วันที่</th>
                    <th>สินค้า</th>
                    <th>จำนวน</th>
                    <th>ส่วนลด</th>
                    <th>ยอดสุทธิ</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($o = mysqli_fetch_assoc($orders_query)): ?>
                <?php
                    $pr = $prs[$o['product_id']] ?? null;
                    $si = $status_map[$o['status']] ?? ['secondary', $o['status']];
                    $disc = $pr ? max(0, $pr['price'] * $o['qty'] - $o['final_price']) : 0;
                ?>
                <tr class="text-center">
                    <td class="fw-bold text-success"><?= $o['order_no'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($o['create_at'])) ?></td>
                    <td class="text-start"><?= htmlspecialchars($pr['name'] ?? '-') ?><br><small class="text-muted"><?= htmlspecialchars($o['buy_name']) ?> (<?= htmlspecialchars($o['buy_tel']) ?>)</small></td>
                    <td><?= $o['qty'] ?></td>
                    <td class="text-danger"><?= $disc > 0 ? '-฿' . number_format($disc, 2) : '-' ?></td>
                    <td class="fw-bold text-success">฿<?= number_format($o['final_price'], 2) ?></td>
                    <td><span class="badge bg-<?= $si[0] ?>"><?= $si[1] ?></span></td>
                </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($orders_query) == 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">ยังไม่มีประวัติคำสั่งซื้อ</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>

<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>