<?php
// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include 'config.php';

// ตรวจสอบว่าตำแหน่งไฟล์ปัจจุบันอยู่ในโฟลเดอร์ admin หรือไม่
$current_script_path = $_SERVER['PHP_SELF'];
$is_inside_admin = (strpos($current_script_path, '/admin/') !== false);

// กำหนด path อ้างอิงโฟลเดอร์หลัก และโฟลเดอร์ admin
if ($is_inside_admin) {
    $base = '../';
    $admin = '';
} else {
    $base = '';
    $admin = 'admin/';
}

$current_page = basename($current_script_path);

// ดึงรหัสองค์กรจากเซสชัน (ถ้ามี)
if (isset($_SESSION['company_id'])) {
    $cid = (int)$_SESSION['company_id'];
} else {
    $cid = 0;
}

$company = null;
$pack_name = '';
$unread_count = 0;

if ($cid > 0) {
    // ดึงข้อมูลบริษัท
    $sql_com = "SELECT * FROM company WHERE id = $cid";
    $query_com = mysqli_query($conn, $sql_com);
    $company = mysqli_fetch_assoc($query_com);

    // ดึงแพ็กเกจที่ใช้งานได้ปัจจุบัน
    $sql_pack = "SELECT members.*, pack.name AS pack_name FROM members JOIN pack ON pack.id = members.pack_id WHERE members.company_id = $cid AND members.status_mem = 'Y' AND (members.end_mem IS NULL OR members.end_mem >= CURDATE()) ORDER BY members.id DESC LIMIT 1";
    $query_pack = mysqli_query($conn, $sql_pack);
    $active_pack = mysqli_fetch_assoc($query_pack);

    if ($active_pack) {
        $pack_name = $active_pack['pack_name'];
    }

    // นับจำนวนการแจ้งเตือนที่ยังไม่ได้อ่าน
    $sql_unread = "SELECT * FROM noti WHERE recip_id = $cid AND is_read = 0";
    $query_unread = mysqli_query($conn, $sql_unread);
    $unread_count = mysqli_num_rows($query_unread);
}

// ตรวจสอบว่าเป็นผู้ดูแลระบบ (Admin: role_id = 1) หรือไม่
$is_admin = false;
if ($company && isset($company['role_id']) && $company['role_id'] == 1) {
    $is_admin = true;
}

// กำหนดเมนูสำหรับผู้ใช้ทั่วไป
$user_menu = array();
if ($pack_name == '') {
    // กรณียังไม่มีแพ็กเกจ
    $user_menu = array(
        'index.php'   => 'หน้าหลัก',
        'product.php' => 'ร้านค้า',
        'news.php'    => 'ข่าวสาร',
        'rank.php'    => 'ดูการจัดอันดับ',
        'pack.php'    => 'สมัครแพ็กเกจ'
    );
} else {
    // กรณีมีแพ็กเกจแล้ว
    $user_menu = array(
        'dashboard.php' => 'แดชบอร์ด',
        'utility.php'   => 'บันทึกค่าน้ำ-ค่าไฟ',
        'product.php'   => 'ร้านค้า',
        'news.php'      => 'ข่าวสาร',
        'rank.php'      => 'ดูการจัดอันดับ',
        'pack.php'      => 'เปลี่ยนแพ็กเกจ'
    );

    // ถ้าเป็นแพ็กเกจ Premium ให้เปิดเมนูภารกิจ
    $is_premium = (stripos($pack_name, 'premium') !== false || strpos($pack_name, 'พรีเมี่ยม') !== false);
    if ($is_premium) {
        $user_menu['mission.php'] = 'ภารกิจ';
    }
}

// เมนูสำหรับผู้ดูแลระบบ (Admin)
$admin_review_menu = array(
    'dashboard.php'          => 'แดชบอร์ด',
    'mission_review.php'     => 'ตรวจหลักฐานภารกิจ',
    'utility_review.php'     => 'ตรวจหลักฐานบันทึกค่าน้ำ-ไฟ',
    'pack_review.php'        => 'อนุมัติแพ็คเกจ',
    'certificate_review.php' => 'อนุมัติใบรับรอง'
);

$admin_manage_menu = array(
    'product_edit.php'  => 'จัดการสินค้า',
    'product_order.php' => 'จัดการคำสั่งซื้อ',
    'mission_edit.php'  => 'จัดการภารกิจ',
    'news_edit.php'     => 'จัดการข่าวสาร',
    'user_edit.php'     => 'จัดการสมาชิก',
    'pack_edit.php'     => 'จัดการแพ็คเกจ',
    'divider'           => '',
    'setting.php'       => 'ตั้งค่าระบบ'
);
?>
<link rel="stylesheet" href="<?php echo $base; ?>css/bootstrap.min.css">
<style>
    .bg-green {
        background-color: #08543c;
    }
    .btn-green {
        background-color: #08543c;
        color: #ffffff;
    }
    .btn-green:hover {
        background-color: #023c2a;
        color: #ffffff;
    }
    .card-white {
        border-radius: 13px;
        transition: transform 0.2s ease;
    }
    .card-white:hover {
        transform: translateY(-3px);
    }
    .card-green {
        color: #ffffff;
        background-color: #08543c;
        border-radius: 13px;
        transition: transform 0.2s ease;
    }
    .card-green:hover {
        transform: translateY(-3px);
    }
    .text-green {
        color: #08543c;
    }
    .bg-orange {
        background-color: #f87d09;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark mb-4 shadow-sm bg-green">
    <div class="container">
        <!-- โลโก้เว็บไซต์ -->
        <a href="<?php echo $base; ?>index.php" class="navbar-brand">
            <img src="<?php echo $base; ?>img/Logo.png" style="height: 45px;" alt="Ecolyn Logo">
        </a>

        <!-- ปุ่มเปิดเมนูบนมือถือ -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <?php if ($is_admin) { ?>
                    <!-- เมนูหลักของ Admin -->
                    <?php foreach ($admin_review_menu as $file_name => $menu_label) { ?>
                        <li class="nav-item">
                            <a class="nav-link <?php if ($current_page == $file_name) { echo 'active fw-bold'; } ?>" href="<?php echo $admin . $file_name; ?>">
                                <?php echo $menu_label; ?>
                            </a>
                        </li>
                    <?php } ?>

                    <!-- เมนูดรอปดาวน์จัดการระบบสำหรับ Admin -->
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">เมนูจัดการระบบ</a>
                        <ul class="dropdown-menu">
                            <?php foreach ($admin_manage_menu as $file_name => $menu_label) { ?>
                                <?php if ($file_name == 'divider') { ?>
                                    <li><hr class="dropdown-divider"></li>
                                <?php } else { ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo $admin . $file_name; ?>">
                                            <?php echo $menu_label; ?>
                                        </a>
                                    </li>
                                <?php } ?>
                            <?php } ?>
                        </ul>
                    </li>

                <?php } else if ($company) { ?>
                    <!-- เมนูสำหรับสมาชิกทั่วไป -->
                    <?php foreach ($user_menu as $file_name => $menu_label) { ?>
                        <li class="nav-item">
                            <a class="nav-link <?php if ($current_page == $file_name) { echo 'active fw-bold'; } ?>" href="<?php echo $base . $file_name; ?>">
                                <?php echo $menu_label; ?>
                            </a>
                        </li>
                    <?php } ?>
                <?php } ?>
            </ul>

            <ul class="navbar-nav align-items-center">
                <?php if ($company) { ?>
                    <!-- กระดิ่งแจ้งเตือน -->
                    <li class="nav-item me-2">
                        <a class="nav-link" href="<?php echo $base; ?>noti.php">
                            🔔 <?php if ($unread_count > 0) { ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $unread_count; ?></span>
                            <?php } ?>
                        </a>
                    </li>

                    <!-- เมนูโปรไฟล์ผู้ใช้ -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white fw-bold" href="#" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($company['name']); ?>
                            <?php if ($is_admin) { ?>
                                <span class="badge bg-warning text-dark ms-1">Admin</span>
                            <?php } ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo $base; ?>pass.php">เปลี่ยนรหัสผ่าน</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $base; ?>logout.php">ออกจากระบบ</a></li>
                        </ul>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</nav>
