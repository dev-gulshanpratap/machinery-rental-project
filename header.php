<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'MachineryRent' ?> | Industrial Machinery Rental</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;900&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/css/main.css">
</head>
<body>

<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
<div class="flash-alert flash-<?= $flash['type'] ?>" id="flashAlert">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
    <?= htmlspecialchars($flash['message']) ?>
    <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
</div>
<?php endif; ?>

<nav class="navbar">
    <div class="nav-inner">
        <a href="<?= SITE_URL ?>/index.php" class="nav-logo">
            <span class="logo-icon"><i class="fas fa-industry"></i></span>
            <span class="logo-text">Machinery<strong>Rent</strong></span>
        </a>
        <ul class="nav-links">
            <li><a href="<?= SITE_URL ?>/index.php" <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'class="active"' : '' ?>>Home</a></li>
            <li><a href="<?= SITE_URL ?>/pages/machines.php" <?= basename($_SERVER['PHP_SELF']) === 'machines.php' ? 'class="active"' : '' ?>>Machines</a></li>
            <li><a href="<?= SITE_URL ?>/pages/categories.php">Categories</a></li>
            <li><a href="<?= SITE_URL ?>/pages/about.php">About</a></li>
            <li><a href="<?= SITE_URL ?>/pages/contact.php">Contact</a></li>
        </ul>
        <div class="nav-actions">
            <?php if (isLoggedIn()): ?>
                <?php
                $db = getDB();
                $notif = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
                $notif->execute([$_SESSION['user_id']]);
                $unread = $notif->fetchColumn();
                ?>
                <a href="<?= SITE_URL ?>/pages/notifications.php" class="btn-icon">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread > 0): ?><span class="badge"><?= $unread ?></span><?php endif; ?>
                </a>
                <div class="user-dropdown">
                    <button class="user-btn">
                        <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                        <span><?= htmlspecialchars($_SESSION['name']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?= SITE_URL ?>/pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a href="<?= SITE_URL ?>/pages/my-rentals.php"><i class="fas fa-file-contract"></i> My Rentals</a>
                        <a href="<?= SITE_URL ?>/pages/invoices.php"><i class="fas fa-file-invoice"></i> Invoices</a>
                        <a href="<?= SITE_URL ?>/pages/profile.php"><i class="fas fa-user-cog"></i> Profile</a>
                        <?php if (isAdmin()): ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= SITE_URL ?>/admin/index.php" class="admin-link"><i class="fas fa-shield-alt"></i> Admin Panel</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= SITE_URL ?>/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn btn-outline">Login</a>
                <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
            <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</nav>
