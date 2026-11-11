<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include '../config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ถ้ายังไม่ล็อกอินให้กลับไปหน้า login
if (!isset($_SESSION['company_id'])) {
    header('location: ../login.php');
    exit();
}

$cid = (int)$_SESSION['company_id'];

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin: role_id = 1)
$sql_check_admin = "SELECT * FROM company WHERE id = $cid";
$query_admin = mysqli_query($conn, $sql_check_admin);
$me = mysqli_fetch_assoc($query_admin);

if (!$me || $me['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

// กำหนดสถานะคำสั่งซื้อทั้งหมด: ค่าในฐานข้อมูล => array(ชื่อภาษาไทย, สี badge)
$status_list = array(
    'pending'  => array('label' => 'รอดำเนินการ', 'color' => 'warning text-dark'),
    'paid'     => array('label' => 'ชำระเงินแล้ว', 'color' => 'info text-dark'),
    'ship'     => array('label' => 'จัดส่งแล้ว',   'color' => 'primary'),
    'complete' => array('label' => 'สำเร็จ',       'color' => 'success'),
    'cancel'   => array('label' => 'ยกเลิก',      'color' => 'danger')
);

// เมื่อผู้ดูแลระบบเปลี่ยนสถานะคำสั่งซื้อ
if (isset($_POST['order_id']) && isset($_POST['status'])) {
    $oid = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    $sql_find_order = "SELECT * FROM product_order WHERE id = $oid";
    $query_find_order = mysqli_query($conn, $sql_find_order);
    $od = mysqli_fetch_assoc($query_find_order);

    if ($od && isset($status_list[$new_status])) {
        // อัปเดตสถานะในตาราง product_order
        $sql_update_order = "UPDATE product_order SET status = '$new_status' WHERE id = $oid";
        mysqli_query($conn, $sql_update_order);

        // ส่งการแจ้งเตือนไปยังบริษัทสมาชิก
        $target_cid = (int)$od['company_id'];
        $status_thai_name = $status_list[$new_status]['label'];
        $noti_msg = "คำสั่งซื้อ {$od['order_no']} เปลี่ยนสถานะเป็น $status_thai_name";
        $escaped_noti_msg = mysqli_real_escape_string($conn, $noti_msg);

        $sql_insert_noti = "INSERT INTO noti (recip_id, message, type, link, create_at) VALUES ($target_cid, '$escaped_noti_msg', 'order', 'product.php', NOW())";
        mysqli_query($conn, $sql_insert_noti);

        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_text'] = 'เปลี่ยนสถานะคำสั่งซื้อสำเร็จเรียบร้อยแล้ว';
    } else {
        $_SESSION['flash_type'] = 'danger';
        $_SESSION['flash_text'] = 'ไม่พบคำสั่งซื้อ หรือข้อมูลสถานะไม่ถูกต้อง';
    }

    header('location: product_order.php');
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

// ดึงรายการสินค้าทั้งหมดเก็บไว้ใน array ตาม id_pro
$products_map = array();
$sql_products = "SELECT * FROM product";
$query_products = mysqli_query($conn, $sql_products);

while ($p_row = mysqli_fetch_assoc($query_products)) {
    $p_id = (int)$p_row['id_pro'];
    $products_map[$p_id] = $p_row;
}

// ดึงรายการคำสั่งซื้อทั้งหมด เรียงจากใหม่ไปเก่า
$sql_orders = "SELECT * FROM product_order ORDER BY id DESC";
$order_result = mysqli_query($conn, $sql_orders);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ - Ecolyn Admin</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container-fluid px-4">
        <h3 class="fw-bold mt-3 text-center">จัดการคำสั่งซื้อสินค้า</h3>

        <?php if ($alert_text != '') { ?>
            <div class="alert alert-<?php echo $alert_type; ?> p-2 text-center mt-3">
                <?php echo $alert_text; ?>
            </div>
        <?php } ?>

        <div class="card shadow-sm p-3 mt-3">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>เลขที่สั่งซื้อ</th>
                            <th class="text-start">สินค้า</th>
                            <th>จำนวน</th>
                            <th>ราคาสุทธิ (บาท)</th>
                            <th class="text-start">ผู้สั่งซื้อ</th>
                            <th class="text-start">ที่อยู่จัดส่ง</th>
                            <th>วิธีชำระ</th>
                            <th>วันที่สั่งซื้อ</th>
                            <th>สถานะ</th>
                            <th style="min-width: 180px;">จัดการสถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($o = mysqli_fetch_assoc($order_result)) { 
                        $pid = (int)$o['product_id'];
                        $pr = isset($products_map[$pid]) ? $products_map[$pid] : null;

                        $cur_status = $o['status'];
                        if (isset($status_list[$cur_status])) {
                            $badge_color = $status_list[$cur_status]['color'];
                            $badge_label = $status_list[$cur_status]['label'];
                        } else {
                            $badge_color = 'secondary';
                            $badge_label = $cur_status;
                        }

                        $payment_method_display = ($o['pay_method'] == 'transfer') ? 'โอนเงิน' : 'เก็บปลายทาง (COD)';
                    ?>
                        <tr>
                            <td class="fw-bold text-success"><?php echo htmlspecialchars($o['order_no']); ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($pr ? $pr['name'] : '-'); ?></td>
                            <td><?php echo (int)$o['qty']; ?></td>
                            <td class="text-success fw-bold">฿<?php echo number_format($o['final_price'], 2); ?></td>
                            <td class="text-start">
                                <strong><?php echo htmlspecialchars($o['buy_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($o['buy_tel']); ?></small>
                            </td>
                            <td class="text-start"><small><?php echo htmlspecialchars($o['ship_addr']); ?></small></td>
                            <td><small><?php echo $payment_method_display; ?></small></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($o['create_at'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $badge_color; ?>">
                                    <?php echo $badge_label; ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" action="product_order.php" class="d-flex gap-1 justify-content-center">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <?php foreach ($status_list as $key => $s_item) { ?>
                                            <option value="<?php echo $key; ?>" <?php if ($o['status'] == $key) { echo 'selected'; } ?>>
                                                <?php echo $s_item['label']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-success">บันทึก</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (mysqli_num_rows($order_result) == 0) { ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">ยังไม่มีรายการคำสั่งซื้อในขณะนี้</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include '../footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
