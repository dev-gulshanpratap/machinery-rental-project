<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'My Invoices';
$db = getDB();
$uid = $_SESSION['user_id'];

// View single invoice
$invId = (int)($_GET['id'] ?? 0);
if ($invId) {
    $inv = $db->prepare("SELECT i.*, r.request_number, r.start_date, r.end_date, r.rental_days, m.name as machine_name, m.model, m.location, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, u.company_name, u.address as customer_address FROM invoices i JOIN rental_requests r ON i.rental_request_id=r.id JOIN machines m ON r.machine_id=m.id JOIN users u ON i.user_id=u.id WHERE i.id=? AND i.user_id=?");
    $inv->execute([$invId, $uid]);
    $inv = $inv->fetch();
    if (!$inv) { setFlash('danger','Invoice not found.'); redirect(SITE_URL.'/pages/invoices.php'); }

    // Mark notifications read
    $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND link LIKE '%invoices%'")->execute([$uid]);
    ?>
    <?php include '../includes/header.php'; ?>
    <main class="main-content">
    <div class="container" style="padding-top:40px;padding-bottom:60px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <a href="invoices.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        <div style="display:flex;gap:10px;">
          <button id="printInvoice" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Print</button>
          <span class="status-badge status-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span>
        </div>
      </div>

      <div class="invoice-paper">
        <!-- Header -->
        <div class="invoice-header">
          <div>
            <div class="nav-logo" style="font-size:1.6rem;margin-bottom:12px;">
              <div class="logo-icon"><i class="fas fa-industry"></i></div>
              <span class="logo-text">Machinery<strong>Rent</strong></span>
            </div>
            <div style="font-size:0.85rem;color:var(--text2);line-height:1.8;">
              MachineryRent Pvt. Ltd.<br>
              Mumbai, Maharashtra 400001<br>
              support@machineryrent.com<br>
              GSTIN: 27AAAAA0000A1Z5
            </div>
          </div>
          <div style="text-align:right;">
            <div class="invoice-number">INVOICE</div>
            <div style="font-size:1rem;color:var(--accent);font-weight:700;"><?= $inv['invoice_number'] ?></div>
            <div style="font-size:0.85rem;color:var(--text2);margin-top:8px;line-height:1.8;">
              Issue Date: <?= date('M d, Y', strtotime($inv['issue_date'])) ?><br>
              Due Date: <?= date('M d, Y', strtotime($inv['due_date'])) ?><br>
              Request: <?= $inv['request_number'] ?>
            </div>
          </div>
        </div>

        <!-- Bill To -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;">
          <div style="background:var(--surface);border-radius:8px;padding:16px;">
            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--text3);margin-bottom:10px;">Bill To</div>
            <div style="font-weight:700;"><?= htmlspecialchars($inv['customer_name']) ?></div>
            <?php if ($inv['company_name']): ?><div style="color:var(--text2);"><?= htmlspecialchars($inv['company_name']) ?></div><?php endif; ?>
            <div style="color:var(--text2);font-size:0.88rem;margin-top:4px;"><?= htmlspecialchars($inv['customer_email']) ?></div>
            <?php if ($inv['customer_phone']): ?><div style="color:var(--text2);font-size:0.88rem;"><?= htmlspecialchars($inv['customer_phone']) ?></div><?php endif; ?>
          </div>
          <div style="background:var(--surface);border-radius:8px;padding:16px;">
            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--text3);margin-bottom:10px;">Rental Details</div>
            <div><strong><?= htmlspecialchars($inv['machine_name']) ?></strong></div>
            <div style="font-size:0.88rem;color:var(--text2);"><?= htmlspecialchars($inv['model'] ?? '') ?></div>
            <div style="font-size:0.85rem;color:var(--text2);margin-top:4px;">
              <?= date('M d, Y', strtotime($inv['start_date'])) ?> → <?= date('M d, Y', strtotime($inv['end_date'])) ?><br>
              Duration: <?= $inv['rental_days'] ?> days
            </div>
          </div>
        </div>

        <!-- Line Items -->
        <table class="invoice-table">
          <thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th style="text-align:right;">Amount</th></tr></thead>
          <tbody>
            <tr>
              <td><?= htmlspecialchars($inv['machine_name']) ?> Rental</td>
              <td><?= $inv['rental_days'] ?> days</td>
              <td><?= formatCurrency($inv['subtotal'] / $inv['rental_days']) ?>/day</td>
              <td style="text-align:right;"><?= formatCurrency($inv['subtotal']) ?></td>
            </tr>
            <?php if ($inv['deposit_amount'] > 0): ?>
            <tr>
              <td>Security Deposit (refundable)</td>
              <td>1</td>
              <td>-</td>
              <td style="text-align:right;"><?= formatCurrency($inv['deposit_amount']) ?></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- Totals -->
        <div style="display:flex;justify-content:flex-end;margin-top:24px;">
          <div class="invoice-totals">
            <div class="invoice-total-row"><span>Subtotal</span><span><?= formatCurrency($inv['subtotal']) ?></span></div>
            <div class="invoice-total-row"><span>GST (<?= $inv['tax_rate'] ?>%)</span><span><?= formatCurrency($inv['tax_amount']) ?></span></div>
            <?php if ($inv['deposit_amount'] > 0): ?>
            <div class="invoice-total-row"><span>Security Deposit</span><span><?= formatCurrency($inv['deposit_amount']) ?></span></div>
            <?php endif; ?>
            <?php if ($inv['discount_amount'] > 0): ?>
            <div class="invoice-total-row"><span>Discount</span><span>-<?= formatCurrency($inv['discount_amount']) ?></span></div>
            <?php endif; ?>
            <div class="invoice-total-row final"><span>Total Due</span><span><?= formatCurrency($inv['total_amount']) ?></span></div>
          </div>
        </div>

        <!-- Footer -->
        <div style="margin-top:40px;padding-top:24px;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:0.78rem;color:var(--text3);">
          <span>Thank you for choosing MachineryRent!</span>
          <span>For queries: support@machineryrent.com</span>
        </div>
      </div>
    </div>
    </main>
    <?php include '../includes/footer.php'; ?>
    <?php
    exit;
}

// Invoice list
$invoices = $db->prepare("SELECT i.*, m.name as machine_name, r.rental_days FROM invoices i JOIN rental_requests r ON i.rental_request_id=r.id JOIN machines m ON r.machine_id=m.id WHERE i.user_id=? ORDER BY i.created_at DESC");
$invoices->execute([$uid]);
$invoices = $invoices->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<main class="main-content">
<div class="page-header">
  <div class="container">
    <h1>My Invoices</h1>
  </div>
</div>
<div class="container section-sm">
  <?php if (empty($invoices)): ?>
  <div class="empty-state"><i class="fas fa-file-invoice"></i><h3>No Invoices Yet</h3><p>Invoices are generated after rental approval.</p></div>
  <?php else: ?>
  <div class="panel"><div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Invoice #</th><th>Machine</th><th>Issue Date</th><th>Due Date</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($invoices as $inv): ?>
      <tr>
        <td><strong class="text-accent"><?= $inv['invoice_number'] ?></strong></td>
        <td><?= htmlspecialchars($inv['machine_name']) ?></td>
        <td><?= date('M d, Y', strtotime($inv['issue_date'])) ?></td>
        <td><?= date('M d, Y', strtotime($inv['due_date'])) ?></td>
        <td class="fw-bold"><?= formatCurrency($inv['total_amount']) ?></td>
        <td><span class="status-badge status-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
        <td><a href="?id=<?= $inv['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> View</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
  <?php endif; ?>
</div>
</main>
<?php include '../includes/footer.php'; ?>
