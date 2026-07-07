<?php
$pageTitle = 'Rental Requests';
require_once 'admin-header.php';
$db = getDB();

// Handle actions
$action = sanitize($_GET['action'] ?? '');
$id     = (int)($_GET['id'] ?? 0);

if ($action === 'approve' && $id) {
    $req = $db->prepare("SELECT * FROM rental_requests WHERE id=?"); $req->execute([$id]); $req = $req->fetch();
    if ($req && $req['status'] === 'pending') {
        $db->prepare("UPDATE rental_requests SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?")->execute([$_SESSION['user_id'], $id]);
        $db->prepare("UPDATE machines SET status='rented' WHERE id=?")->execute([$req['machine_id']]);

        // Generate invoice
        $invNum = generateInvoiceNumber();
        $tax = $req['subtotal'] * (TAX_RATE / 100);
        $db->prepare("INSERT INTO invoices (invoice_number,rental_request_id,user_id,issue_date,due_date,subtotal,tax_rate,tax_amount,deposit_amount,total_amount,balance_due,status) VALUES (?,?,?,NOW(),?,?,?,?,?,?,?,'sent')")
           ->execute([$invNum, $id, $req['user_id'], $req['start_date'], $req['subtotal'], TAX_RATE, $tax, $req['deposit_amount'], $req['total_amount'], $req['total_amount']]);

        addNotification($req['user_id'], 'Rental Approved! 🎉', "Your request {$req['request_number']} has been approved. Invoice generated.", 'success', SITE_URL.'/pages/invoices.php');
        setFlash('success', 'Request approved and invoice generated!');
    }
    redirect(SITE_URL . '/admin/rental-requests.php');
}

if ($action === 'reject' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = sanitize($_POST['rejection_reason'] ?? '');
    $req = $db->prepare("SELECT * FROM rental_requests WHERE id=?"); $req->execute([$id]); $req = $req->fetch();
    $db->prepare("UPDATE rental_requests SET status='rejected', rejection_reason=? WHERE id=?")->execute([$reason, $id]);
    addNotification($req['user_id'], 'Rental Request Rejected', "Your request {$req['request_number']} was not approved. Reason: $reason", 'danger', SITE_URL.'/pages/my-rentals.php');
    setFlash('warning', 'Request rejected.');
    redirect(SITE_URL . '/admin/rental-requests.php');
}

if ($action === 'complete' && $id) {
    $req = $db->prepare("SELECT * FROM rental_requests WHERE id=?"); $req->execute([$id]); $req = $req->fetch();
    $db->prepare("UPDATE rental_requests SET status='completed' WHERE id=?")->execute([$id]);
    $db->prepare("UPDATE machines SET status='available', total_rentals=total_rentals+1 WHERE id=?")->execute([$req['machine_id']]);
    $db->prepare("UPDATE invoices SET status='paid', payment_date=NOW(), amount_paid=total_amount, balance_due=0 WHERE rental_request_id=?")->execute([$id]);
    addNotification($req['user_id'], 'Rental Completed', "Your rental {$req['request_number']} has been marked as completed.", 'info');
    setFlash('success', 'Rental marked as completed.');
    redirect(SITE_URL . '/admin/rental-requests.php');
}

// Filters
$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['q'] ?? '');
$where = ['1=1']; $params = [];
if ($status) { $where[] = 'r.status=?'; $params[] = $status; }
if ($search) { $where[] = '(r.request_number LIKE ? OR u.name LIKE ? OR m.name LIKE ?)'; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }
$whereStr = implode(' AND ', $where);

$requests = $db->prepare("SELECT r.*, m.name as machine_name, u.name as customer_name, u.email as customer_email, u.company_name FROM rental_requests r JOIN machines m ON r.machine_id=m.id JOIN users u ON r.user_id=u.id WHERE $whereStr ORDER BY FIELD(r.status,'pending','approved','active','completed','rejected','cancelled'), r.created_at DESC");
$requests->execute($params);
$requests = $requests->fetchAll();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
  <div>
    <h2 style="font-size:1.8rem;font-weight:900;">Rental Requests</h2>
    <p style="color:var(--text2);font-size:0.88rem;"><?= count($requests) ?> requests found</p>
  </div>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar">
  <div class="filter-group" style="flex:2;">
    <label>Search</label>
    <div class="filter-search">
      <input type="text" name="q" class="form-control" placeholder="Request #, customer, machine..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
    </div>
  </div>
  <div class="filter-group">
    <label>Status</label>
    <select name="status" class="form-control" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach(['pending','approved','active','completed','rejected','cancelled'] as $s): ?>
      <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="panel">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Request #</th>
          <th>Customer</th>
          <th>Machine</th>
          <th>Duration</th>
          <th>Total</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($requests)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text3);">No requests found.</td></tr>
      <?php endif; ?>
      <?php foreach ($requests as $r): ?>
      <tr>
        <td>
          <strong class="text-accent"><?= $r['request_number'] ?></strong>
          <div style="font-size:0.72rem;color:var(--text3);"><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
        </td>
        <td>
          <div style="font-weight:600;"><?= htmlspecialchars($r['customer_name']) ?></div>
          <div style="font-size:0.75rem;color:var(--text3);"><?= htmlspecialchars($r['customer_email']) ?></div>
          <?php if ($r['company_name']): ?><div style="font-size:0.72rem;color:var(--text3);"><?= htmlspecialchars($r['company_name']) ?></div><?php endif; ?>
        </td>
        <td><?= htmlspecialchars($r['machine_name']) ?></td>
        <td>
          <div style="font-weight:600;"><?= $r['rental_days'] ?> days</div>
          <div style="font-size:0.75rem;color:var(--text3);"><?= date('M d', strtotime($r['start_date'])) ?> – <?= date('M d', strtotime($r['end_date'])) ?></div>
        </td>
        <td class="fw-bold text-accent"><?= formatCurrency($r['total_amount']) ?></td>
        <td><span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
        <td>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <?php if ($r['status'] === 'pending'): ?>
            <a href="?action=approve&id=<?= $r['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve this request?')">
              <i class="fas fa-check"></i> Approve
            </a>
            <button onclick="openRejectModal(<?= $r['id'] ?>)" class="btn btn-danger btn-sm">
              <i class="fas fa-times"></i> Reject
            </button>
            <?php elseif ($r['status'] === 'approved' || $r['status'] === 'active'): ?>
            <a href="?action=complete&id=<?= $r['id'] ?>" class="btn btn-blue btn-sm" onclick="return confirm('Mark as completed?')">
              <i class="fas fa-flag-checkered"></i> Complete
            </a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Reject Rental Request</h3>
      <button onclick="document.getElementById('rejectModal').classList.remove('open')" style="background:none;border:none;color:var(--text2);font-size:1.2rem;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" id="rejectForm">
      <input type="hidden" name="rejection_reason" id="rejectReasonInput" value="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Reason for Rejection <span class="required">*</span></label>
          <textarea id="rejectReason" class="form-control" rows="3" placeholder="Explain why this request is being rejected..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="document.getElementById('rejectModal').classList.remove('open')" class="btn btn-outline">Cancel</button>
        <button type="button" onclick="submitReject()" class="btn btn-danger"><i class="fas fa-times"></i> Reject Request</button>
      </div>
    </form>
  </div>
</div>

<script>
let rejectId = 0;
function openRejectModal(id) {
  rejectId = id;
  document.getElementById('rejectForm').action = '?action=reject&id=' + id;
  document.getElementById('rejectModal').classList.add('open');
}
function submitReject() {
  const reason = document.getElementById('rejectReason').value.trim();
  if (!reason) { alert('Please provide a rejection reason.'); return; }
  document.getElementById('rejectReasonInput').value = reason;
  document.getElementById('rejectForm').submit();
}
</script>

<?php include 'admin-footer.php'; ?>
