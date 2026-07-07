<?php
require_once dirname(__DIR__) . '/includes/config.php';
requireAdmin();

// Get pending counts for badges
$db = getDB();
$pendingRequests = $db->query("SELECT COUNT(*) FROM rental_requests WHERE status='pending'")->fetchColumn();
$maintenanceDue  = $db->query("SELECT COUNT(*) FROM maintenance_records WHERE status='scheduled'")->fetchColumn();
$unreadNotifs    = $db->query("SELECT COUNT(*) FROM notifications WHERE user_id=".$_SESSION['user_id']." AND is_read=0")->fetchColumn();

$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($pages) {
    global $currentPage;
    return in_array($currentPage, (array)$pages) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Admin' ?> | MachineryRent Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;900&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/css/main.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/css/admin.css">
</head>
<body>

<?php $flash = getFlash(); if ($flash): ?>
<div class="flash-alert flash-<?= $flash['type'] ?>" id="flashAlert">
  <i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash['message']) ?>
  <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
</div>
<?php endif; ?>

<div class="admin-layout">
<aside class="admin-sidebar">
  <div class="sidebar-header">
    <div class="logo-icon"><i class="fas fa-industry"></i></div>
    <span>Machinery<strong style="color:var(--accent);">Rent</strong></span>
    <span style="font-size:9px;background:var(--accent);color:#000;padding:2px 6px;border-radius:3px;margin-left:auto;font-weight:700;">ADMIN</span>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Overview</div>
    <a href="index.php" class="sidebar-link <?= isActive('index.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="analytics.php" class="sidebar-link <?= isActive('analytics.php') ?>"><i class="fas fa-chart-bar"></i> Analytics</a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Rentals</div>
    <a href="rental-requests.php" class="sidebar-link <?= isActive('rental-requests.php') ?>">
      <i class="fas fa-file-contract"></i> Rental Requests
      <?php if ($pendingRequests > 0): ?><span class="link-badge"><?= $pendingRequests ?></span><?php endif; ?>
    </a>
    <a href="invoices.php" class="sidebar-link <?= isActive(['invoices.php','invoice-create.php']) ?>"><i class="fas fa-file-invoice-dollar"></i> Invoices</a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Inventory</div>
    <a href="machines.php" class="sidebar-link <?= isActive(['machines.php','machine-add.php','machine-edit.php']) ?>"><i class="fas fa-tractor"></i> Machines</a>
    <a href="categories.php" class="sidebar-link <?= isActive(['categories.php']) ?>"><i class="fas fa-tags"></i> Categories</a>
    <a href="maintenance.php" class="sidebar-link <?= isActive('maintenance.php') ?>">
      <i class="fas fa-tools"></i> Maintenance
      <?php if ($maintenanceDue > 0): ?><span class="link-badge"><?= $maintenanceDue ?></span><?php endif; ?>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Users</div>
    <a href="customers.php" class="sidebar-link <?= isActive('customers.php') ?>"><i class="fas fa-users"></i> Customers</a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Settings</div>
    <a href="<?= SITE_URL ?>/pages/profile.php" class="sidebar-link"><i class="fas fa-user-cog"></i> My Profile</a>
    <a href="<?= SITE_URL ?>/index.php" class="sidebar-link" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a>
    <a href="<?= SITE_URL ?>/logout.php" class="sidebar-link" style="color:var(--red);"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</aside>

<div class="admin-main">
<div class="admin-topbar">
  <div style="display:flex;align-items:center;gap:12px;">
    <button id="sidebarToggle" style="background:none;border:none;color:var(--text);font-size:1.1rem;cursor:pointer;display:none;"><i class="fas fa-bars"></i></button>
    <div style="font-size:0.85rem;color:var(--text3);">
      <i class="fas fa-shield-alt" style="color:var(--accent);"></i> Admin Panel
    </div>
  </div>
  <div style="display:flex;align-items:center;gap:12px;">
    <div style="font-size:0.85rem;color:var(--text2);">
      <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['name']) ?>
    </div>
  </div>
</div>
<div class="admin-content">
