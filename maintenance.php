<?php
$pageTitle = 'Maintenance';
require_once 'admin-header.php';
$db = getDB();

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_maintenance'])) {
    $editId = (int)($_POST['edit_id'] ?? 0);
    $fields = ['machine_id','maintenance_type','title','description','technician_name','technician_contact','start_date','end_date','cost','parts_used','status','priority','next_maintenance_date','notes'];
    $data = [];
    foreach ($fields as $f) $data[$f] = sanitize($_POST[$f] ?? '');
    $data['created_by'] = $_SESSION['user_id'];

    if ($editId) {
        unset($data['created_by']);
        $sets = implode(', ', array_map(fn($k) => "$k=?", array_keys($data)));
        $db->prepare("UPDATE maintenance_records SET $sets WHERE id=?")->execute([...array_values($data), $editId]);
        setFlash('success','Maintenance record updated!');
    } else {
        $cols = implode(',', array_keys($data));
        $vals = implode(',', array_fill(0, count($data), '?'));
        $db->prepare("INSERT INTO maintenance_records ($cols) VALUES ($vals)")->execute(array_values($data));
        // Update machine status if needed
        if ($data['status'] === 'in_progress') {
            $db->prepare("UPDATE machines SET status='maintenance' WHERE id=?")->execute([$data['machine_id']]);
        }
        setFlash('success','Maintenance record added!');
    }
    redirect(SITE_URL.'/admin/maintenance.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $db->prepare("DELETE FROM maintenance_records WHERE id=?")->execute([(int)$_POST['delete_id']]);
    setFlash('success','Record deleted.'); redirect(SITE_URL.'/admin/maintenance.php');
}

$statusFilter = sanitize($_GET['status'] ?? '');
$where = ['1=1']; $params = [];
if ($statusFilter) { $where[] = 'mr.status=?'; $params[] = $statusFilter; }

$records = $db->prepare("SELECT mr.*, m.name as machine_name FROM maintenance_records mr JOIN machines m ON mr.machine_id=m.id WHERE ".implode(' AND ',$where)." ORDER BY FIELD(mr.priority,'critical','high','medium','low'), mr.start_date DESC");
$records->execute($params);
$records = $records->fetchAll();

$machines = $db->query("SELECT id, name FROM machines ORDER BY name")->fetchAll();

$editRec = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM maintenance_records WHERE id=?"); $stmt->execute([(int)$_GET['edit']]); $editRec = $stmt->fetch();
}

$priorityColors = ['critical'=>'red','high'=>'orange','medium'=>'yellow','low'=>'blue'];
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
  <div>
    <h2 style="font-size:1.8rem;font-weight:900;">Maintenance Tracking</h2>
    <p style="color:var(--text2);font-size:0.88rem;"><?= count($records) ?> records</p>
  </div>
  <button data-modal="addMaintenanceModal" class="btn btn-primary"><i class="fas fa-plus"></i> Add Record</button>
</div>

<!-- Summary Cards -->
<?php
$summary = $db->query("SELECT status, COUNT(*) as cnt FROM maintenance_records GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
  <div class="stat-card"><div class="stat-icon yellow"><i class="fas fa-calendar"></i></div><div class="stat-info"><h3><?= $summary['scheduled'] ?? 0 ?></h3><p>Scheduled</p></div></div>
  <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-cog fa-spin"></i></div><div class="stat-info"><h3><?= $summary['in_progress'] ?? 0 ?></h3><p>In Progress</p></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check-circle"></i></div><div class="stat-info"><h3><?= $summary['completed'] ?? 0 ?></h3><p>Completed</p></div></div>
  <div class="stat-card"><div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-info"><h3><?= $db->query("SELECT COUNT(*) FROM maintenance_records WHERE priority='critical'")->fetchColumn() ?></h3><p>Critical</p></div></div>
</div>

<!-- Filter -->
<form method="GET" style="margin-bottom:20px;">
  <div style="display:flex;gap:10px;align-items:center;">
    <?php foreach(['','scheduled','in_progress','completed','cancelled'] as $s): ?>
    <button type="submit" name="status" value="<?= $s ?>" class="btn <?= $statusFilter===$s ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $s ? ucwords(str_replace('_',' ',$s)) : 'All' ?></button>
    <?php endforeach; ?>
  </div>
</form>

<div class="panel">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Machine</th><th>Type</th><th>Title</th><th>Technician</th><th>Dates</th><th>Cost</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($records)): ?>
      <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text3);">No maintenance records found.</td></tr>
      <?php endif; ?>
      <?php foreach ($records as $r): ?>
      <tr>
        <td><strong><?= htmlspecialchars($r['machine_name']) ?></strong></td>
        <td><span style="text-transform:capitalize;"><?= str_replace('_',' ',$r['maintenance_type']) ?></span></td>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td>
          <div><?= htmlspecialchars($r['technician_name'] ?? 'N/A') ?></div>
          <div style="font-size:0.72rem;color:var(--text3);"><?= htmlspecialchars($r['technician_contact'] ?? '') ?></div>
        </td>
        <td style="font-size:0.78rem;">
          <div><?= date('M d, Y', strtotime($r['start_date'])) ?></div>
          <?php if ($r['end_date']): ?><div style="color:var(--text3);">End: <?= date('M d, Y', strtotime($r['end_date'])) ?></div><?php endif; ?>
        </td>
        <td><?= $r['cost'] > 0 ? formatCurrency($r['cost']) : '-' ?></td>
        <td>
          <span class="status-badge status-<?= $priorityColors[$r['priority']] ?? 'yellow' ?>" style="text-transform:capitalize;">
            <?= $r['priority'] ?>
          </span>
        </td>
        <td><span class="status-badge status-<?= str_replace('_','-',$r['status']) ?>"><?= ucwords(str_replace('_',' ',$r['status'])) ?></span></td>
        <td>
          <div style="display:flex;gap:6px;">
            <a href="?edit=<?= $r['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i></a>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
              <button class="btn btn-danger btn-sm" data-confirm="Delete this record?"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="addMaintenanceModal">
  <div class="modal-box" style="max-width:620px;">
    <div class="modal-header">
      <h3><?= $editRec ? 'Edit' : 'Add' ?> Maintenance Record</h3>
      <button onclick="this.closest('.modal-overlay').classList.remove('open')" style="background:none;border:none;color:var(--text2);font-size:1.2rem;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="save_maintenance" value="1">
      <input type="hidden" name="edit_id" value="<?= $editRec['id'] ?? 0 ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Machine *</label>
            <select name="machine_id" class="form-control" required>
              <?php foreach($machines as $m): ?>
              <option value="<?= $m['id'] ?>" <?= ($editRec['machine_id'] ?? '')==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Type *</label>
            <select name="maintenance_type" class="form-control" required>
              <?php foreach(['routine','repair','inspection','emergency'] as $t): ?>
              <option value="<?= $t ?>" <?= ($editRec['maintenance_type'] ?? '')===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($editRec['title'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($editRec['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Technician Name</label>
            <input type="text" name="technician_name" class="form-control" value="<?= htmlspecialchars($editRec['technician_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Technician Contact</label>
            <input type="text" name="technician_contact" class="form-control" value="<?= htmlspecialchars($editRec['technician_contact'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date *</label>
            <input type="date" name="start_date" class="form-control" required value="<?= $editRec['start_date'] ?? '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= $editRec['end_date'] ?? '' ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Cost (₹)</label>
            <input type="number" name="cost" class="form-control" step="0.01" value="<?= $editRec['cost'] ?? 0 ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-control">
              <?php foreach(['low','medium','high','critical'] as $p): ?>
              <option value="<?= $p ?>" <?= ($editRec['priority'] ?? 'medium')===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach(['scheduled','in_progress','completed','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= ($editRec['status'] ?? 'scheduled')===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Next Maintenance Date</label>
            <input type="date" name="next_maintenance_date" class="form-control" value="<?= $editRec['next_maintenance_date'] ?? '' ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Parts Used</label>
          <textarea name="parts_used" class="form-control" rows="2" placeholder="List parts replaced..."><?= htmlspecialchars($editRec['parts_used'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="this.closest('.modal-overlay').classList.remove('open')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Record</button>
      </div>
    </form>
  </div>
</div>
<?php if ($editRec) echo "<script>document.getElementById('addMaintenanceModal').classList.add('open');</script>"; ?>
<?php include 'admin-footer.php'; ?>
