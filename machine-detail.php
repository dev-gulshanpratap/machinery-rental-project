<?php
require_once '../includes/config.php';
$pageTitle = 'Machine Details';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$machine = $db->prepare("SELECT m.*, c.name as cat_name FROM machines m JOIN categories c ON m.category_id=c.id WHERE m.id=?");
$machine->execute([$id]);
$m = $machine->fetch();
if (!$m) { setFlash('danger','Machine not found.'); redirect(SITE_URL.'/pages/machines.php'); }

$pageTitle = $m['name'];

$reviews = $db->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.machine_id=? ORDER BY r.created_at DESC LIMIT 5");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

$avgRating = $db->prepare("SELECT AVG(rating) FROM reviews WHERE machine_id=?");
$avgRating->execute([$id]);
$avgRating = round($avgRating->fetchColumn(), 1);

// Handle rental request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $startDate = sanitize($_POST['start_date'] ?? '');
    $endDate   = sanitize($_POST['end_date'] ?? '');
    $purpose   = sanitize($_POST['purpose'] ?? '');
    $siteAddr  = sanitize($_POST['site_address'] ?? '');
    $operator  = (int)($_POST['operator_required'] ?? 0);

    $errors = [];
    if (!$startDate || !$endDate) $errors[] = 'Please select rental dates.';
    if ($startDate && $endDate && $startDate >= $endDate) $errors[] = 'End date must be after start date.';

    if (empty($errors)) {
        $days      = ceil((strtotime($endDate) - strtotime($startDate)) / 86400);
        $subtotal  = $days * $m['daily_rate'];
        $tax       = $subtotal * (TAX_RATE / 100);
        $total     = $subtotal + $tax + $m['deposit_amount'];
        $reqNum    = generateRequestNumber();

        $stmt = $db->prepare("INSERT INTO rental_requests (request_number,user_id,machine_id,start_date,end_date,rental_days,purpose,site_address,operator_required,subtotal,tax_amount,deposit_amount,total_amount) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$reqNum, $_SESSION['user_id'], $id, $startDate, $endDate, $days, $purpose, $siteAddr, $operator, $subtotal, $tax, $m['deposit_amount'], $total]);

        // Notify admin
        $admins = $db->query("SELECT id FROM users WHERE role='admin'")->fetchAll();
        foreach ($admins as $admin) {
            addNotification($admin['id'], 'New Rental Request', "Request $reqNum submitted for {$m['name']}", 'info', SITE_URL.'/admin/rental-requests.php');
        }
        // Notify customer
        addNotification($_SESSION['user_id'], 'Request Submitted!', "Your rental request $reqNum is pending approval.", 'success', SITE_URL.'/pages/my-rentals.php');

        setFlash('success', "Rental request $reqNum submitted successfully! We'll notify you once approved.");
        redirect(SITE_URL . '/pages/my-rentals.php');
    }
}
?>
<?php include '../includes/header.php'; ?>
<main class="main-content">
<div class="page-header">
  <div class="container">
    <div class="breadcrumb">
      <a href="<?= SITE_URL ?>">Home</a><span class="sep">/</span>
      <a href="machines.php">Machines</a><span class="sep">/</span>
      <span class="current"><?= htmlspecialchars($m['name']) ?></span>
    </div>
    <h1><?= htmlspecialchars($m['name']) ?></h1>
    <p><?= htmlspecialchars($m['manufacturer']) ?> · <?= $m['year_of_manufacture'] ?> · <?= htmlspecialchars($m['location']) ?></p>
  </div>
</div>

<div class="container section-sm">
  <div style="display:grid;grid-template-columns:1fr 380px;gap:32px;align-items:start;">

    <!-- Left -->
    <div>
      <div class="machine-gallery" style="margin-bottom:24px;">
        <i class="fas fa-tractor"></i>
      </div>

      <!-- Info Panel -->
      <div class="panel">
        <div class="panel-header">
          <h3>Machine Overview</h3>
          <span class="status-badge status-<?= $m['status'] ?>"><?= ucfirst($m['status']) ?></span>
        </div>
        <div class="panel-body">
          <p style="color:var(--text2);margin-bottom:20px;"><?= htmlspecialchars($m['description'] ?? 'No description available.') ?></p>
          <div class="spec-grid">
            <?php
            $specs = [
              'Category'    => $m['cat_name'],
              'Model'       => $m['model'] ?? 'N/A',
              'Manufacturer'=> $m['manufacturer'] ?? 'N/A',
              'Year'        => $m['year_of_manufacture'] ?? 'N/A',
              'Capacity'    => $m['capacity'] ?? 'N/A',
              'Weight'      => $m['weight'] ?? 'N/A',
              'Fuel Type'   => $m['fuel_type'] ?? 'N/A',
              'Horsepower'  => $m['horsepower'] ?? 'N/A',
              'Location'    => $m['location'],
              'Condition'   => $m['condition_rating'].'/10',
            ];
            foreach ($specs as $label => $val): ?>
            <div class="spec-item">
              <label><?= $label ?></label>
              <span><?= htmlspecialchars($val) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Reviews -->
      <div class="panel mt-3">
        <div class="panel-header">
          <h3>Customer Reviews</h3>
          <?php if ($avgRating): ?>
          <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:1.4rem;font-weight:900;color:var(--accent);"><?= $avgRating ?></span>
            <div class="stars"><?php for($i=1;$i<=5;$i++) echo $i<=$avgRating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star empty"></i>'; ?></div>
          </div>
          <?php endif; ?>
        </div>
        <div class="panel-body">
          <?php if (empty($reviews)): ?>
          <div class="empty-state" style="padding:30px 0;">
            <i class="fas fa-star"></i>
            <p>No reviews yet. Be the first to review!</p>
          </div>
          <?php else: ?>
          <?php foreach ($reviews as $r): ?>
          <div style="border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
              <strong><?= htmlspecialchars($r['user_name']) ?></strong>
              <div class="stars" style="font-size:0.8rem;">
                <?php for($i=1;$i<=5;$i++) echo $i<=$r['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star empty"></i>'; ?>
              </div>
            </div>
            <p style="font-size:0.88rem;color:var(--text2);"><?= htmlspecialchars($r['comment']) ?></p>
            <span style="font-size:0.75rem;color:var(--text3);"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right - Booking Card -->
    <div style="position:sticky;top:80px;">
      <div class="panel">
        <div class="panel-header">
          <h3>Book This Machine</h3>
        </div>
        <div class="panel-body">
          <!-- Pricing -->
          <div style="background:var(--surface);border-radius:8px;padding:16px;margin-bottom:20px;">
            <div style="font-size:0.75rem;color:var(--text3);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Rental Rates</div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);">
              <span style="color:var(--text2);font-size:0.88rem;">Daily Rate</span>
              <strong><?= formatCurrency($m['daily_rate']) ?></strong>
            </div>
            <?php if ($m['weekly_rate']): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);">
              <span style="color:var(--text2);font-size:0.88rem;">Weekly Rate</span>
              <strong><?= formatCurrency($m['weekly_rate']) ?></strong>
            </div>
            <?php endif; ?>
            <?php if ($m['monthly_rate']): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);">
              <span style="color:var(--text2);font-size:0.88rem;">Monthly Rate</span>
              <strong><?= formatCurrency($m['monthly_rate']) ?></strong>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;">
              <span style="color:var(--text2);font-size:0.88rem;">Security Deposit</span>
              <strong class="text-accent"><?= formatCurrency($m['deposit_amount']) ?></strong>
            </div>
          </div>

          <?php if ($m['status'] !== 'available'): ?>
          <div style="background:#291200;border:1px solid rgba(245,158,11,0.3);border-radius:8px;padding:14px;text-align:center;color:var(--accent);">
            <i class="fas fa-clock"></i> This machine is currently <?= $m['status'] ?> and not available for booking.
          </div>
          <?php elseif (!isLoggedIn()): ?>
          <div style="text-align:center;padding:16px;">
            <p style="color:var(--text2);margin-bottom:14px;">Please login to book this machine.</p>
            <a href="<?= SITE_URL ?>/login.php" class="btn btn-primary btn-full">Login to Book</a>
          </div>
          <?php else: ?>
          <form method="POST">
            <input type="hidden" name="daily_rate_val" value="<?= $m['daily_rate'] ?>">
            <div class="form-group">
              <label class="form-label">Start Date <span class="required">*</span></label>
              <input type="date" name="start_date" id="start_date" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">End Date <span class="required">*</span></label>
              <input type="date" name="end_date" id="end_date" class="form-control" required>
            </div>

            <input type="hidden" id="daily_rate" value="<?= $m['daily_rate'] ?>">
            <div id="rental-summary" style="display:none;background:var(--surface);border-radius:8px;padding:14px;margin-bottom:16px;font-size:0.88rem;">
              <div style="display:flex;justify-content:space-between;padding:4px 0;"><span>Duration</span><strong id="calc-days">-</strong></div>
              <div style="display:flex;justify-content:space-between;padding:4px 0;"><span>Subtotal</span><strong id="calc-sub">-</strong></div>
              <div style="display:flex;justify-content:space-between;padding:4px 0;"><span>GST (18%)</span><strong id="calc-tax">-</strong></div>
              <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--border);margin-top:4px;"><span style="font-weight:700;">Total</span><strong class="text-accent" id="calc-total">-</strong></div>
            </div>

            <div class="form-group">
              <label class="form-label">Site Address</label>
              <textarea name="site_address" class="form-control" rows="2" placeholder="Delivery location..."></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Purpose / Notes</label>
              <textarea name="purpose" class="form-control" rows="2" placeholder="Brief description of use..."></textarea>
            </div>
            <div class="form-group">
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="operator_required" value="1" style="width:16px;height:16px;">
                <span style="font-size:0.88rem;">I need an operator with the machine</span>
              </label>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-lg">
              <i class="fas fa-file-alt"></i> Submit Rental Request
            </button>
            <p style="font-size:0.75rem;color:var(--text3);text-align:center;margin-top:8px;">Request will be reviewed and approved by admin</p>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>
</main>
<?php include '../includes/footer.php'; ?>
