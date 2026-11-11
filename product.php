<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ถ้ายังไม่ล็อกอินให้กลับไปหน้า login
if (!isset($_SESSION['company_id'])) {
    header('location: login.php');
    exit();
}

$cid = (int)$_SESSION['company_id'];

// ดึงข้อมูลบริษัทของผู้ใช้งานปัจจุบัน
$sql_me = "SELECT * FROM company WHERE id = $cid";
$query_me = mysqli_query($conn, $sql_me);
$me = mysqli_fetch_assoc($query_me);

// กำหนดแท็บที่กำลังเปิดอยู่ (สินค้า หรือ ประวัติคำสั่งซื้อ)
if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
} else {
    $active_tab = 'products';
}

// 1. คำนวณแต้มสะสมคงเหลือของบริษัทนี้
$points = 0;
$sql_points = "SELECT * FROM eco_point_ledger WHERE company_id = $cid";
$query_points = mysqli_query($conn, $sql_points);

while ($p_row = mysqli_fetch_assoc($query_points)) {
    $point_num = (int)$p_row['point'];
    $points = $points + $point_num;
}

// 2. ดึงรายการสินค้าทั้งหมดเก็บไว้ใน array ตามรหัส id_pro
$products_map = array();
$sql_all_pr = "SELECT * FROM product";
$query_all_pr = mysqli_query($conn, $sql_all_pr);

while ($item = mysqli_fetch_assoc($query_all_pr)) {
    $item_id = (int)$item['id_pro'];
    $products_map[$item_id] = $item;
}

// 3. จัดการสั่งซื้อสินค้าเมื่อมีการส่งข้อมูลแบบ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];

    if (isset($products_map[$product_id])) {
        $product = $products_map[$product_id];
    } else {
        $product = null;
    }

    $qty = (int)$_POST['qty'];
    if ($qty < 1) {
        $qty = 1;
    }

    $buy_name = mysqli_real_escape_string($conn, trim($_POST['buy_name']));
    $buy_tel = mysqli_real_escape_string($conn, trim($_POST['buy_tel']));
    $ship_addr = mysqli_real_escape_string($conn, trim($_POST['ship_addr']));

    if (isset($_POST['pay_method']) && $_POST['pay_method'] == 'transfer') {
        $pay_method = 'transfer';
    } else {
        $pay_method = 'cod';
    }

    // ตรวจสอบเงื่อนไขการสั่งซื้อ
    if (!$product || $product['status'] != 'active') {
        $_SESSION['flash_type'] = 'danger';
        $_SESSION['flash_text'] = 'ไม่พบสินค้า หรือสินค้านี้ปิดการขายแล้ว';
    } else if ($product['num'] < $qty) {
        $_SESSION['flash_type'] = 'warning';
        $_SESSION['flash_text'] = 'สินค้ามีจำนวนไม่พอ (คงเหลือ ' . $product['num'] . ' ชิ้น)';
    } else if ($buy_name == '' || $buy_tel == '' || $ship_addr == '') {
        $_SESSION['flash_type'] = 'warning';
        $_SESSION['flash_text'] = 'กรุณากรอกข้อมูลผู้รับและที่อยู่จัดส่งให้ครบถ้วน';
    } else {
        // คำนวณราคาและส่วนลดจากแต้ม
        $unit_price = (int)$product['price'];
        $total_price = $unit_price * $qty;

        $max_discount_per_item = (int)$product['point_disc'];
        $max_discount_total = $max_discount_per_item * $qty;

        $input_points = (int)$_POST['points_used'];
        if ($input_points < 0) {
            $input_points = 0;
        }

        // คำนวณแต้มที่ใช้ได้จริง (ไม่เกินแต้มที่กรอก, ไม่เกินเพดานส่วนลด, ไม่เกินราคาสินค้า, และไม่เกินแต้มสะสมที่มี)
        $actual_points_used = $input_points;
        if ($actual_points_used > $max_discount_total) {
            $actual_points_used = $max_discount_total;
        }
        if ($actual_points_used > $total_price) {
            $actual_points_used = $total_price;
        }
        if ($actual_points_used > $points) {
            $actual_points_used = $points;
        }

        $final_price = $total_price - $actual_points_used;
        $order_no = 'ORD' . date('Ymd') . rand(1000, 9999);
        $pid = (int)$product['id_pro'];

        // บันทึกคำสั่งซื้อลงฐานข้อมูล
        $sql_insert_order = "INSERT INTO product_order (order_no, company_id, product_id, qty, final_price, buy_name, buy_tel, ship_addr, pay_method, status, create_at) VALUES ('$order_no', $cid, $pid, $qty, '$final_price', '$buy_name', '$buy_tel', '$ship_addr', '$pay_method', 'pending', NOW())";
        mysqli_query($conn, $sql_insert_order);

        // ลดจำนวนสต็อกสินค้า
        $sql_update_stock = "UPDATE product SET num = num - $qty WHERE id_pro = $pid";
        mysqli_query($conn, $sql_update_stock);

        // ตัดแต้มสะสมและบันทึกประวัติการใช้แต้ม (ถ้ามีการใช้แต้ม)
        if ($actual_points_used > 0) {
            $product_name = $product['name'];
            $reason = mysqli_real_escape_string($conn, "ใช้แต้มซื้อ $product_name ($order_no)");
            $negative_points = -$actual_points_used;
            $sql_point_deduct = "INSERT INTO eco_point_ledger (company_id, point, reason) VALUES ($cid, $negative_points, '$reason')";
            mysqli_query($conn, $sql_point_deduct);
        }

        // แจ้งเตือนผู้ดูแลระบบทุกคน
        $company_name = $me['name'];
        $noti_msg = mysqli_real_escape_string($conn, "$company_name สั่งซื้อสินค้าใหม่ เลขที่ $order_no");
        $sql_admins = "SELECT * FROM company WHERE role_id = 1";
        $admins_query = mysqli_query($conn, $sql_admins);

        while ($admin = mysqli_fetch_assoc($admins_query)) {
            $admin_id = (int)$admin['id'];
            $sql_send_noti = "INSERT INTO noti (recip_id, message, type, link, create_at) VALUES ($admin_id, '$noti_msg', 'order', 'admin/product_order.php', NOW())";
            mysqli_query($conn, $sql_send_noti);
        }

        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_text'] = 'สั่งซื้อเรียบร้อย! รหัสคำสั่งซื้อ: <strong>' . $order_no . '</strong>';
        header('location: product.php?tab=orders');
        exit();
    }

    header('location: product.php');
    exit();
}

// ดึงข้อความแจ้งเตือนจากเซสชัน
$alert_type = '';
$alert_text = '';

if (isset($_SESSION['flash_type'])) {
    $alert_type = $_SESSION['flash_type'];
    $alert_text = $_SESSION['flash_text'];
    unset($_SESSION['flash_type']);
    unset($_SESSION['flash_text']);
}

// ค้นหาสินค้า
$search_query = '';
if (isset($_GET['q'])) {
    $search_query = trim($_GET['q']);
}

$escaped_q = mysqli_real_escape_string($conn, $search_query);
$where_products = "WHERE status = 'active'";
if ($search_query != '') {
    $where_products = $where_products . " AND (name LIKE '%$escaped_q%' OR detail LIKE '%$escaped_q%')";
}

$sql_products_query = "SELECT * FROM product $where_products ORDER BY id_pro DESC";
$products_query = mysqli_query($conn, $sql_products_query);

// ดึงประวัติคำสั่งซื้อของบริษัทนี้
$sql_orders_query = "SELECT * FROM product_order WHERE company_id = $cid ORDER BY id DESC";
$orders_query = mysqli_query($conn, $sql_orders_query);

// แมปปิ้งสถานะคำสั่งซื้อเป็นสีและข้อความ
$status_badge_map = array(
    'pending'  => array('color' => 'warning text-dark', 'text' => 'รอเตรียมจัดส่ง'),
    'paid'     => array('color' => 'info text-dark',    'text' => 'ชำระเงินแล้ว'),
    'ship'     => array('color' => 'primary',           'text' => 'จัดส่งแล้ว'),
    'complete' => array('color' => 'success',           'text' => 'สำเร็จ'),
    'cancel'   => array('color' => 'danger',            'text' => 'ยกเลิก')
);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านค้าผลิตภัณฑ์รักษ์โลก - Ecolyn</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mt-3">
            <h3 class="fw-bold mb-0">ร้านค้าผลิตภัณฑ์รักษ์โลก</h3>
            <span class="badge bg-success fs-6 p-2">แต้มของคุณ: <?php echo number_format($points); ?> แต้ม</span>
        </div>

        <?php if ($alert_text != '') { ?>
            <div class="alert alert-<?php echo $alert_type; ?> p-2 text-center mt-3">
                <?php echo $alert_text; ?>
            </div>
        <?php } ?>

        <!-- เมนูแท็บ และช่องค้นหา -->
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link <?php if ($active_tab === 'products') { echo 'active bg-success'; } else { echo 'text-dark'; } ?>" href="product.php?tab=products">สินค้าทั้งหมด</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if ($active_tab === 'orders') { echo 'active bg-success'; } else { echo 'text-dark'; } ?>" href="product.php?tab=orders">
                        คำสั่งซื้อของฉัน (<?php echo mysqli_num_rows($orders_query); ?>)
                    </a>
                </li>
            </ul>

            <?php if ($active_tab === 'products') { ?>
                <form class="d-flex gap-2" method="get" action="product.php">
                    <input type="text" name="q" class="form-control" placeholder="ค้นหาสินค้า..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-success">ค้นหา</button>
                    <?php if ($search_query != '') { ?>
                        <a href="product.php" class="btn btn-outline-secondary">ล้าง</a>
                    <?php } ?>
                </form>
            <?php } ?>
        </div>

        <!-- แท็บแสดงรายการสินค้า -->
        <?php if ($active_tab === 'products') { ?>
            <div class="row">
                <?php 
                while ($p = mysqli_fetch_assoc($products_query)) { 
                    $pid = (int)$p['id_pro'];
                    $stock_num = (int)$p['num'];
                    $can_order = ($stock_num > 0);
                ?>
                    <div class="col-md-4 mt-3">
                        <div class="card card-white shadow-sm d-flex flex-column p-2 h-100" <?php if ($can_order) { echo 'data-bs-toggle="modal" data-bs-target="#buyModal_' . $pid . '" style="cursor: pointer;"'; } ?>>
                            <?php if ($p['pic'] != '') { ?>
                                <img src="img/<?php echo htmlspecialchars($p['pic']); ?>" class="card-img-top border" style="height: 200px; object-fit: cover;">
                            <?php } else { ?>
                                <div class="bg-secondary text-white d-flex justify-content-center align-items-center" style="height: 200px;">
                                    ไม่มีรูปภาพ
                                </div>
                            <?php } ?>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="fw-bold"><?php echo htmlspecialchars($p['name']); ?></h5>
                                    <?php if ($can_order) { ?>
                                        <span class="badge bg-info text-dark">เหลือ <?php echo $stock_num; ?> ชิ้น</span>
                                    <?php } else { ?>
                                        <span class="badge bg-danger">สินค้าหมด</span>
                                    <?php } ?>
                                </div>
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($p['detail']); ?></p>
                                <div class="d-flex justify-content-between align-items-end mt-auto pt-3">
                                    <div>
                                        <div><b class="text-success fs-5"><?php echo number_format($p['price'], 2); ?></b> บาท</div>
                                        <small class="text-muted">ลดได้สูงสุด <?php echo number_format($p['point_disc']); ?> แต้ม</small>
                                    </div>
                                    <button type="button" class="btn btn-success" <?php if (!$can_order) { echo 'disabled'; } ?>>
                                        สั่งซื้อสินค้า
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ป็อปอัป (Modal) สำหรับสั่งซื้อสินค้า -->
                    <div class="modal fade" id="buyModal_<?php echo $pid; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <form method="post" action="product.php">
                                    <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold"><?php echo htmlspecialchars($p['name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php if ($p['pic'] != '') { ?>
                                            <img src="img/<?php echo htmlspecialchars($p['pic']); ?>" class="w-100 mb-3" style="max-height: 250px; object-fit: cover;">
                                        <?php } ?>
                                        <p><?php echo nl2br(htmlspecialchars($p['detail'])); ?></p>
                                        <p class="mb-3">
                                            <b class="text-success fs-5">฿<?php echo number_format($p['price'], 2); ?></b>
                                            <span class="badge bg-success ms-2">ลดได้สูงสุด <?php echo number_format($p['point_disc']); ?> แต้ม/ชิ้น</span>
                                        </p>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">จำนวนที่ต้องการสั่งซื้อ (เหลือ <?php echo $stock_num; ?> ชิ้น)</label>
                                                <input type="number" name="qty" class="form-control" value="1" min="1" max="<?php echo $stock_num; ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">ใช้แต้มลดราคา (คุณมี <?php echo number_format($points); ?> แต้ม)</label>
                                                <input type="number" name="points_used" class="form-control" value="0" min="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">ชื่อผู้รับ</label>
                                                <input type="text" name="buy_name" class="form-control" required value="<?php echo htmlspecialchars($me['name']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">เบอร์โทรศัพท์</label>
                                                <input type="text" name="buy_tel" class="form-control" required placeholder="08xxxxxxxx" value="<?php echo htmlspecialchars($me['tel']); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">ที่อยู่จัดส่ง</label>
                                                <textarea name="ship_addr" class="form-control" rows="2" required placeholder="กรุณากรอกที่อยู่จัดส่งสินค้าโดยละเอียด"></textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">วิธีชำระเงิน</label>
                                                <select name="pay_method" class="form-select">
                                                    <option value="cod">เก็บเงินปลายทาง (COD)</option>
                                                    <option value="transfer">โอนเงินเข้าบัญชี</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                        <button type="submit" class="btn btn-success" onclick="return confirm('ยืนยันการสั่งซื้อสินค้า?')">ยืนยันสั่งซื้อ</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <?php if (mysqli_num_rows($products_query) == 0) { ?>
                <p class="text-muted text-center mt-4">ไม่พบรายการสินค้า</p>
            <?php } ?>

        <!-- แท็บแสดงประวัติคำสั่งซื้อ -->
        <?php } else { ?>
            <div class="card shadow-sm p-3 mt-3">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-center">
                            <th>รหัสคำสั่งซื้อ</th>
                            <th>วันที่สั่งซื้อ</th>
                            <th>รายการสินค้า</th>
                            <th>จำนวน</th>
                            <th>ส่วนลดจากแต้ม</th>
                            <th>ยอดชำระสุทธิ</th>
                            <th>สถานะคำสั่งซื้อ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($o = mysqli_fetch_assoc($orders_query)) { 
                        $order_pid = (int)$o['product_id'];
                        $pr = isset($products_map[$order_pid]) ? $products_map[$order_pid] : null;

                        // ตรวจสอบสถานะ
                        $order_status = $o['status'];
                        if (isset($status_badge_map[$order_status])) {
                            $badge_color = $status_badge_map[$order_status]['color'];
                            $badge_text = $status_badge_map[$order_status]['text'];
                        } else {
                            $badge_color = 'secondary';
                            $badge_text = $order_status;
                        }

                        // คำนวณส่วนลด
                        $discount_amount = 0;
                        if ($pr) {
                            $normal_total = $pr['price'] * $o['qty'];
                            $discount_amount = $normal_total - $o['final_price'];
                            if ($discount_amount < 0) {
                                $discount_amount = 0;
                            }
                        }
                    ?>
                        <tr class="text-center">
                            <td class="fw-bold text-success"><?php echo htmlspecialchars($o['order_no']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($o['create_at'])); ?></td>
                            <td class="text-start">
                                <strong><?php echo htmlspecialchars($pr ? $pr['name'] : '-'); ?></strong><br>
                                <small class="text-muted">ผู้รับ: <?php echo htmlspecialchars($o['buy_name']); ?> (<?php echo htmlspecialchars($o['buy_tel']); ?>)</small>
                            </td>
                            <td><?php echo $o['qty']; ?></td>
                            <td class="text-danger">
                                <?php 
                                if ($discount_amount > 0) {
                                    echo '-฿' . number_format($discount_amount, 2);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td class="fw-bold text-success">฿<?php echo number_format($o['final_price'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $badge_color; ?>">
                                    <?php echo $badge_text; ?>
                                </span>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (mysqli_num_rows($orders_query) == 0) { ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">ยังไม่มีประวัติคำสั่งซื้อ</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include 'footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
