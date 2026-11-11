<?php
include 'config.php';

$in_admin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$base = $in_admin ? '../' : '';
$admin = $in_admin ? '' : 'admin/';
$page = basename($_SERVER['PHP_SELF']);

$cid = $_SESSION['company_id'] ?? 0;
$company = null;
$pack_name = '';
$unread = 0;

if($cid){
    $company = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$cid"));
    $pack = mysqli_fetch_assoc(mysqli_query($conn, "select * from members join pack on pack.id = members.pack_id where members.company_id=$cid and members.status_mem='Y' and (members.end_mem is null or members.end_mem >= curdate()) order by members.id desc limit 1"));
    if($pack) $pack_name = $pack['name'];
    $unread = mysqli_num_rows(mysqli_query($conn, "select * from noti where recip_id=$cid and is_read=0"));
}

$is_admin = ($company['role_id'] ?? 0) == 1;

if($pack_name == ''){
    $menu = [
        'index.php'=>'หน้าหลัก', 
        'product.php'=>'ร้านค้า', 
        'news.php'=>'ข่าวสาร', 
        'rank.php'=>'ดูการจัดอันดับ', 
        'pack.php'=>'สมัครแพ็กเกจ'
        ];
}else{
    $menu = [
        'dashboard.php'=>'แดชบอร์ด', 
        'utility.php'=>'บันทึกค่าน้ำ-ค่าไฟ', 
        'product.php'=>'ร้านค้า', 
        'news.php'=>'ข่าวสาร', 
        'rank.php'=>'ดูการจัดอันดับ', 
        'pack.php'=>'เปลี่ยนแพ็กเกจ'
    ];
    if(stripos($pack_name, 'premium') !== false || strpos($pack_name, 'พรีเมี่ยม') !== false){
    $menu['mission.php'] = 'ภารกิจ';
}
}

$admin_menu = [
    'dashboard.php'=>'แดชบอร์ด', 
    'mission_review.php'=>'ตรวจหลักฐานภารกิจ', 
    'utility_review.php'=>'ตรวจหลักฐานบันทึกค่าน้ำ-ไฟ', 
    'pack_review.php'=>'อนุมัติแพ็คเกจ', 
    'certificate_review.php'=>'อนุมัติใบรับรอง'
    ];
$manage_menu = [
    'product_edit.php'=>'จัดการสินค้า', 
    'product_order.php'=>'จัดการคำสั่งซื้อ', 
    'mission_edit.php'=>'จัดการภารกิจ', 
    'news_edit.php'=>'จัดการข่าวสาร', 
    'user_edit.php'=>'จัดการสมาชิก', 
    'pack_edit.php'=>'จัดการแพ็คเกจ', 
    '-1'   => '',
    'setting.php'=>'ตั้งค่าระบบ'
    ];
?>
<link rel="stylesheet" href="<?= $base ?>css/bootstrap.min.css">
<style>
    .bg-green{
        background-color: #08543c;
    }
    
    .btn-green{
        background: #08543c;
        color:white;
    }
    .btn-green:hover{
        background: #023c2a;
        color:white;
    }

    .card-white{
        border-radius: 13px;
    }
    .card-white:hover{
    transform: translateY(-3px);
    }

    .card-green{
        color: white;
        background: #08543c;
        border-radius: 13px;
    }
    .card-green:hover{
    transform: translateY(-3px);
    }

    .text-green{
        color: #08543c;
    }
    .bg-orange{
            background: #f87d09;
        }
</style>
<nav class="navbar navbar-expand-lg navbar-dark mb-4 shadow-sm bg-green">
    <div class="container">
        <a href="<?= $base ?>index.php" class="navbar-brand">
            <img src="<?= $base ?>img/Logo.png" style="height:45px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse bg" id="navMenu">
            <ul class="navbar-nav me-auto">

                <?php if($is_admin): ?>
                    <?php foreach($admin_menu as $file => $label): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == $file ? 'active fw-bold' : '' ?>" href="<?= $admin . $file ?>"><?= $label ?></a>
                        </li>
                    <?php endforeach; ?>

                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">เมนูจัดการระบบ</a>
                        <ul class="dropdown-menu">
                            <?php foreach($manage_menu as $file => $label): ?>
                                <?php if($label): ?>
                                    <li><a class="dropdown-item" href="<?= $admin . $file ?>"><?= $label ?></a></li>
                                <?php else: ?>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </li>

                <?php elseif($company): ?>
                    <?php foreach($menu as $file => $label): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == $file ? 'active fw-bold' : '' ?>" href="<?= $base . $file ?>"><?= $label ?></a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>

            </ul>

            <ul class="navbar-nav align-items-center">
                <?php if($company): ?>

                    <li class="nav-item me-2">
                        <a class="nav-link" href="<?= $base ?>noti.php">
                            🔔 <?php if($unread > 0): ?><span class="badge bg-danger rounded-pill"><?= $unread ?></span><?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white fw-bold" href="#" data-bs-toggle="dropdown">
                            <?= $company['name'] ?>
                            <?php if($is_admin): ?><span class="badge bg-warning text-dark ms-1">Admin</span><?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= $base ?>pass.php">เปลี่ยนรหัสผ่าน</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $base ?>logout.php">ออกจากระบบ</a></li>
                        </ul>
                    </li>

                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
