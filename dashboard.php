<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'My Dashboard';
$db = getDB();
$uid = $_SESSION['user_id'];

$user = $db->prepare("SELECT * FROM users WHERE id=?"); $user->execute([$uid]); $user = $user->fetch();

$stats = [
    'total'     => $db->prepare("SELECT COUNT(*) FROM rental_requests WHERE user_id=?"),
    'active'    => $db->prepare("SELECT COUNT(*) FROM rental_requests WHERE user_id=? AND status='active'"),
    'pending'   => $db->prepare("SELECT COUNT(*) FROM rental_requests WHERE user_id=? AND status='pending'"),
    'completed' => $db->prepare("SELECT COUNT(*) FROM rental_requests WHERE user_id=? AND status='completed'"),
    'spent'     => $db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM rental_requests WHERE user_id=? AND status IN('active','completed')"),
];
foreach ($stats as $key => $stmt) { $stmt->execute([$uid]); $stats[$key] = $stmt->fetchColumn(); }

$recentRequests = $db->prepare("SELECT r.*, m.name as machine_name, m.daily_rate FROM rental_requests r JOIN machines m ON r.machine_id=m.id WHERE r.user_id=? ORDER BY r.created_at DESC LIMIT 5");
$recentRequests->execute([$uid]);
$recentRequests = $recentRequests->fetchAll();

$notifications = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$notifications->execute([$uid]);
$notifications = $notifications->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<main class="main-content">
<div class="page-header">
  <div class="container">
    <h1>Welcome, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</h1>
    <p>Here's your rental activity overview.</p>
  </div>
</div>
<div class="container section-sm">

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fas fa-file-contract"></i></div>
      <div class="stat-info"><h3><?= $stats['total'] ?></h3><p>Total Requests</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
      <div class="stat-info"><h3><?= $stats['pending'] ?></h3><p>Pending Approval</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fas fa-tractor"></i></div>
      <div class="stat-info"><h3><?= $stats['active'] ?></h3><p>Active Rentals</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon purple"><i class="fas fa-rupee-sign"></i></div>
      <div class="stat-info"><h3><?= formatCurrency($stats['spent']) ?></h3><p>Total Spent</p></div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
    <!-- Recent Requests -->
    <div class="panel">
      <div class="panel-header">
        <h3>Recent Rental Requests</h3>
        <a href="my-rentals.php" class="btn btn-outline btn-sm">View All</a>
      </div>
      <?php if (empty($recentRequests)): ?>
      <div class="empty-state">
        <i class="fas fa-file-alt"></i>
        <h3>No Rentals Yet</h3>
        <p>Browse machines and submit your first rental request.</p>
        <a href="machines.php" class="btn btn-primary mt-2">Browse Machines</a>
      </div>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Request #</th><th>Machine</th><th>Dates</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($recentRequests as $r): ?>
          <tr>
            <td><strong><?= $r['request_number'] ?></strong></td>
            <td><?= htmlspecialchars($r['machine_name']) ?></td>
            <td style="font-size:0.8rem;"><?= date('M d', strtotime($r['start_date'])) ?> – <?= date('M d, Y', strtotime($r['end_date'])) ?></td>
            <td class="text-accent"><?= formatCurrency($r['total_amount']) ?></td>
            <td><span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Notifications -->
    <div class="panel">
      <div class="panel-header">
        <h3>Notifications</h3>
        <a href="notifications.php" class="btn btn-outline btn-sm">All</a>
      </div>
      <div class="panel-body" style="padding:0;">
        <?php if (empty($notifications)): ?>
        <div class="empty-state" style="padding:24px;"><i class="fas fa-bell"></i><p>No notifications yet.</p></div>
        <?php else: ?>
        <?php foreach ($notifications as $n): ?>
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);<?= !$n['is_read'] ? 'background:rgba(245,158,11,0.04);' : '' ?>">
          <div style="font-size:0.85rem;font-weight:600;margin-bottom:3px;"><?= htmlspecialchars($n['title']) ?></div>
          <div style="font-size:0.78rem;color:var(--text2);"><?= htmlspecialchars($n['message']) ?></div>
          <div style="font-size:0.72rem;color:var(--text3);margin-top:4px;"><?= date('M d, g:i A', strtotime($n['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="panel mt-3">
    <div class="panel-header"><h3>Quick Actions</h3></div>
    <div class="panel-body">
      <div style="display:flex;gap:14px;flex-wrap:wrap;">
        <a href="machines.php" class="btn btn-primary"><i class="fas fa-search"></i> Browse Machines</a>
        <a href="my-rentals.php" class="btn btn-outline"><i class="fas fa-file-contract"></i> My Rentals</a>
        <a href="invoices.php" class="btn btn-outline"><i class="fas fa-file-invoice"></i> My Invoices</a>
        <a href="profile.php" class="btn btn-outline"><i class="fas fa-user-cog"></i> Edit Profile</a>
      </div>
    </div>
  </div>

</div>
</main>
<?php include '../includes/footer.php'; ?>
