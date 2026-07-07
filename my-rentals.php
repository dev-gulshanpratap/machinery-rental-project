<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'My Rentals';
$db = getDB();
$uid = $_SESSION['user_id'];

$status = sanitize($_GET['status'] ?? '');
$where = ['r.user_id = ?'];
$params = [$uid];
if ($status) { $where[] = 'r.status = ?'; $params[] = $status; }
$whereStr = implode(' AND ', $where);

$requests = $db->prepare("SELECT r.*, m.name as machine_name, m.daily_rate, m.location, c.name as cat_name FROM rental_requests r JOIN machines m ON r.machine_id=m.id JOIN categories c ON m.category_id=c.id WHERE $whereStr ORDER BY r.created_at DESC");
$requests->execute($params);
$requests = $requests->fetchAll();

$statuses = ['pending','approved','active','completed','rejected','cancelled'];
?>
<?php include '../includes/header.php'; ?>
<main class="main-content">
<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="sep">/</span><span class="current">My Rentals</span></div>
    <h1>My Rental Requests</h1>
  </div>
</div>
<div class="container section-sm">
  <!-- Status filter tabs -->
  <div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;">
    <a href="my-rentals.php" class="btn <?= !$status ? 'btn-primary' : 'btn-outline' ?> btn-sm">All</a>
    <?php foreach ($statuses as $s): ?>
    <a href="?status=<?= $s ?>" class="btn <?= $status===$s ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($requests)): ?>
  <div class="empty-state">
    <i class="fas fa-file-contract"></i>
    <h3>No Rentals Found</h3>
    <p>You haven't submitted any rental requests yet.</p>
    <a href="machines.php" class="btn btn-primary mt-2"><i class="fas fa-search"></i> Browse Machines</a>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:16px;">
    <?php foreach ($requests as $r): ?>
    <div class="panel" style="margin:0;">
      <div class="panel-body" style="display:grid;grid-template-columns:1fr auto;gap:16px;align-items:center;">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;align-items:center;">
          <div>
            <div style="font-size:0.72rem;color:var(--text3);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Request #</div>
            <div style="font-weight:700;color:var(--accent);"><?= $r['request_number'] ?></div>
            <div style="font-size:0.75rem;color:var(--text3);margin-top:2px;"><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
          </div>
          <div>
            <div style="font-size:0.72px;color:var(--text3);font-size:0.72rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Machine</div>
            <div style="font-weight:600;"><?= htmlspecialchars($r['machine_name']) ?></div>
            <div style="font-size:0.78rem;color:var(--text2);"><?= htmlspecialchars($r['cat_name']) ?></div>
          </div>
          <div>
            <div style="font-size:0.72rem;color:var(--text3);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Duration</div>
            <div style="font-weight:600;"><?= $r['rental_days'] ?> days</div>
            <div style="font-size:0.78rem;color:var(--text2);"><?= date('M d', strtotime($r['start_date'])) ?> – <?= date('M d, Y', strtotime($r['end_date'])) ?></div>
          </div>
          <div>
            <div style="font-size:0.72rem;color:var(--text3);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Total</div>
            <div style="font-size:1.2rem;font-weight:900;color:var(--accent);"><?= formatCurrency($r['total_amount']) ?></div>
            <span class="status-badge status-<?= $r['payment_status'] ?>"><?= ucfirst($r['payment_status']) ?></span>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:10px;">
          <span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
          <div style="display:flex;gap:8px;">
            <a href="machine-detail.php?id=<?= $r['machine_id'] ?>" class="btn btn-outline btn-sm">Machine</a>
            <?php if ($r['status'] === 'approved' || $r['status'] === 'completed'): ?>
            <a href="invoices.php?request_id=<?= $r['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-file-invoice"></i> Invoice</a>
            <?php endif; ?>
          </div>
          <?php if ($r['status'] === 'rejected' && $r['rejection_reason']): ?>
          <div style="font-size:0.78rem;color:var(--red);max-width:240px;text-align:right;">Reason: <?= htmlspecialchars($r['rejection_reason']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
</main>
<?php include '../includes/footer.php'; ?>
