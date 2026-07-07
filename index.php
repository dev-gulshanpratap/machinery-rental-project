<?php
$pageTitle = 'Dashboard';
require_once 'admin-header.php';

$db = getDB();

// Stats
$stats = [
    'total_machines'   => $db->query("SELECT COUNT(*) FROM machines")->fetchColumn(),
    'available'        => $db->query("SELECT COUNT(*) FROM machines WHERE status='available'")->fetchColumn(),
    'total_customers'  => $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
    'pending_requests' => $db->query("SELECT COUNT(*) FROM rental_requests WHERE status='pending'")->fetchColumn(),
    'active_rentals'   => $db->query("SELECT COUNT(*) FROM rental_requests WHERE status='active'")->fetchColumn(),
    'total_revenue'    => $db->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE status='paid'")->fetchColumn(),
    'this_month_rev'   => $db->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE status='paid' AND MONTH(payment_date)=MONTH(NOW())")->fetchColumn(),
    'maintenance_due'  => $db->query("SELECT COUNT(*) FROM maintenance_records WHERE status='scheduled'")->fetchColumn(),
];

$recentRequests = $db->query("SELECT r.*, m.name as machine_name, u.name as customer_name, u.company_name FROM rental_requests r JOIN machines m ON r.machine_id=m.id JOIN users u ON r.user_id=u.id ORDER BY r.created_at DESC LIMIT 8")->fetchAll();

$recentMaintenance = $db->query("SELECT mr.*, m.name as machine_name FROM maintenance_records mr JOIN machines m ON mr.machine_id=m.id ORDER BY mr.created_at DESC LIMIT 5")->fetchAll();

$topMachines = $db->query("SELECT m.*, c.name as cat_name, COUNT(r.id) as rental_count FROM machines m LEFT JOIN rental_requests r ON m.id=r.machine_id JOIN categories c ON m.category_id=c.id GROUP BY m.id ORDER BY rental_count DESC LIMIT 5")->fetchAll();
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
  <div>
    <h2 style="font-size:1.8rem;font-weight:900;">Dashboard Overview</h2>
    <p style="color:var(--text2);font-size:0.88rem;"><?= date('l, F d, Y') ?></p>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="machines.php" class="btn btn-outline btn-sm"><i class="fas fa-plus"></i> Add Machine</a>
    <a href="rental-requests.php" class="btn btn-primary btn-sm"><i class="fas fa-bell"></i> Pending (<?= $stats['pending_requests'] ?>)</a>
  </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fas fa-tractor"></i></div>
    <div class="stat-info"><h3 data-count="<?= $stats['total_machines'] ?>">0</h3><p>Total Machines</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
    <div class="stat-info"><h3 data-count="<?= $stats['available'] ?>">0</h3><p>Available Now</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
    <div class="stat-info"><h3 data-count="<?= $stats['total_customers'] ?>">0</h3><p>Customers</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-clock"></i></div>
    <div class="stat-info"><h3 data-count="<?= $stats['pending_requests'] ?>">0</h3><p>Pending Requests</p></div>
  </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-top:16px;">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-file-contract"></i></div>
    <div class="stat-info"><h3><?= $stats['active_rentals'] ?></h3><p>Active Rentals</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-rupee-sign"></i></div>
    <div class="stat-info"><h3><?= formatCurrency($stats['total_revenue']) ?></h3><p>Total Revenue</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fas fa-calendar-check"></i></div>
    <div class="stat-info"><h3><?= formatCurrency($stats['this_month_rev']) ?></h3><p>This Month</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fas fa-tools"></i></div>
    <div class="stat-info"><h3><?= $stats['maintenance_due'] ?></h3><p>Maintenance Due</p></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-top:24px;">

  <!-- Recent Requests -->
  <div class="panel">
    <div class="panel-header">
      <h3>Recent Rental Requests</h3>
      <a href="rental-requests.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Request #</th><th>Customer</th><th>Machine</th><th>Dates</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($recentRequests as $r): ?>
        <tr>
          <td><strong class="text-accent"><?= $r['request_number'] ?></strong></td>
          <td>
            <div style="font-weight:600;"><?= htmlspecialchars($r['customer_name']) ?></div>
            <div style="font-size:0.75rem;color:var(--text3);"><?= htmlspecialchars($r['company_name'] ?? '') ?></div>
          </td>
          <td><?= htmlspecialchars($r['machine_name']) ?></td>
          <td style="font-size:0.78rem;"><?= date('M d', strtotime($r['start_date'])) ?> – <?= date('M d', strtotime($r['end_date'])) ?></td>
          <td><span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
          <td>
            <?php if ($r['status'] === 'pending'): ?>
            <a href="rental-requests.php?action=approve&id=<?= $r['id'] ?>" class="btn btn-success btn-sm">Approve</a>
            <?php else: ?>
            <a href="rental-requests.php?id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">View</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top Machines + Maintenance -->
  <div style="display:flex;flex-direction:column;gap:20px;">
    <div class="panel">
      <div class="panel-header"><h3>Top Rented Machines</h3></div>
      <div class="panel-body" style="padding:0;">
        <?php foreach ($topMachines as $m): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--border);">
          <div style="width:40px;height:40px;background:var(--surface);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--accent);">
            <i class="fas fa-tractor"></i>
          </div>
          <div style="flex:1;">
            <div style="font-size:0.88rem;font-weight:600;"><?= htmlspecialchars($m['name']) ?></div>
            <div style="font-size:0.75rem;color:var(--text3);"><?= $m['cat_name'] ?></div>
          </div>
          <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.2rem;font-weight:700;color:var(--accent);"><?= $m['rental_count'] ?>x</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header"><h3>Maintenance Alerts</h3><a href="maintenance.php" class="btn btn-outline btn-sm">Manage</a></div>
      <div class="panel-body" style="padding:0;">
        <?php if (empty($recentMaintenance)): ?>
        <div style="padding:20px;text-align:center;color:var(--text3);font-size:0.85rem;"><i class="fas fa-check-circle" style="color:var(--green);"></i> All clear!</div>
        <?php else: ?>
        <?php foreach ($recentMaintenance as $mr): ?>
        <div style="padding:12px 20px;border-bottom:1px solid var(--border);">
          <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
            <span style="font-size:0.85rem;font-weight:600;"><?= htmlspecialchars($mr['machine_name']) ?></span>
            <span class="status-badge status-<?= $mr['status'] ?>" style="font-size:10px;"><?= ucfirst($mr['status']) ?></span>
          </div>
          <div style="font-size:0.78rem;color:var(--text2);"><?= htmlspecialchars($mr['title']) ?></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include 'admin-footer.php'; ?>
