<?php
$pageTitle = 'Customers';
require_once 'admin-header.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)$_POST['user_id'];
    $status = sanitize($_POST['status']);
    $db->prepare("UPDATE users SET status=? WHERE id=? AND role='customer'")->execute([$status, $uid]);
    setFlash('success','Customer status updated.'); redirect(SITE_URL.'/admin/customers.php');
}

$search = sanitize($_GET['q'] ?? '');
$where = ["role='customer'"]; $params = [];
if ($search) { $where[] = '(name LIKE ? OR email LIKE ? OR company_name LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }

$customers = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM rental_requests r WHERE r.user_id=u.id) as total_requests, (SELECT COALESCE(SUM(total_amount),0) FROM rental_requests r WHERE r.user_id=u.id AND r.status IN('active','completed')) as total_spent FROM users u WHERE ".implode(' AND ',$where)." ORDER BY u.created_at DESC");
$customers->execute($params);
$customers = $customers->fetchAll();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
  <h2 style="font-size:1.8rem;font-weight:900;">Customers</h2>
  <span style="color:var(--text2);font-size:0.88rem;"><?= count($customers) ?> customers registered</span>
</div>

<form method="GET" class="filter-bar">
  <div class="filter-group" style="flex:2;">
    <label>Search</label>
    <div class="filter-search">
      <input type="text" name="q" class="form-control" placeholder="Name, email, company..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
    </div>
  </div>
</form>

<div class="panel">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Customer</th><th>Contact</th><th>Company</th><th>Requests</th><th>Total Spent</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($customers as $c): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;background:var(--accent);color:#000;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;flex-shrink:0;"><?= strtoupper(substr($c['name'],0,1)) ?></div>
            <div>
              <div style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></div>
              <div style="font-size:0.75rem;color:var(--text3);">ID: #<?= $c['id'] ?></div>
            </div>
          </div>
        </td>
        <td>
          <div style="font-size:0.85rem;"><?= htmlspecialchars($c['email']) ?></div>
          <div style="font-size:0.75rem;color:var(--text3);"><?= htmlspecialchars($c['phone'] ?? 'N/A') ?></div>
        </td>
        <td><?= htmlspecialchars($c['company_name'] ?? '-') ?></td>
        <td><?= $c['total_requests'] ?></td>
        <td class="text-accent fw-bold"><?= formatCurrency($c['total_spent']) ?></td>
        <td style="font-size:0.82rem;"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
        <td><span class="status-badge status-<?= $c['status'] === 'active' ? 'available' : 'cancelled' ?>"><?= ucfirst($c['status']) ?></span></td>
        <td>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="user_id" value="<?= $c['id'] ?>">
            <?php if ($c['status'] === 'active'): ?>
            <input type="hidden" name="status" value="inactive">
            <button class="btn btn-danger btn-sm" data-confirm="Deactivate this customer?">Deactivate</button>
            <?php else: ?>
            <input type="hidden" name="status" value="active">
            <button class="btn btn-success btn-sm" data-confirm="Activate this customer?">Activate</button>
            <?php endif; ?>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'admin-footer.php'; ?>
