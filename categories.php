<?php
$pageTitle = 'Categories';
require_once 'admin-header.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $db->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['delete_id']]);
        setFlash('success','Category deleted.'); redirect(SITE_URL.'/admin/categories.php');
    }
    if (isset($_POST['save_category'])) {
        $editId = (int)($_POST['edit_id'] ?? 0);
        $name   = sanitize($_POST['name'] ?? '');
        $slug   = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        $desc   = sanitize($_POST['description'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        if ($editId) {
            $db->prepare("UPDATE categories SET name=?,slug=?,description=?,status=? WHERE id=?")->execute([$name,$slug,$desc,$status,$editId]);
        } else {
            $db->prepare("INSERT INTO categories (name,slug,description,status) VALUES (?,?,?,?)")->execute([$name,$slug,$desc,$status]);
        }
        setFlash('success','Category saved!'); redirect(SITE_URL.'/admin/categories.php');
    }
}

$cats = $db->query("SELECT c.*, COUNT(m.id) as machine_count FROM categories c LEFT JOIN machines m ON c.id=m.category_id GROUP BY c.id ORDER BY c.name")->fetchAll();
$editCat = null;
if (isset($_GET['edit'])) { $stmt=$db->prepare("SELECT * FROM categories WHERE id=?"); $stmt->execute([(int)$_GET['edit']]); $editCat=$stmt->fetch(); }
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
  <h2 style="font-size:1.8rem;font-weight:900;">Categories</h2>
  <button data-modal="catModal" class="btn btn-primary"><i class="fas fa-plus"></i> Add Category</button>
</div>

<div class="panel">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Name</th><th>Slug</th><th>Description</th><th>Machines</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($cats as $c): ?>
      <tr>
        <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
        <td><code style="background:var(--surface);padding:2px 6px;border-radius:4px;font-size:0.8rem;"><?= $c['slug'] ?></code></td>
        <td><?= htmlspecialchars(substr($c['description'] ?? '', 0, 60)) ?>...</td>
        <td><?= $c['machine_count'] ?></td>
        <td><span class="status-badge status-<?= $c['status'] === 'active' ? 'available' : 'cancelled' ?>"><?= ucfirst($c['status']) ?></span></td>
        <td>
          <div style="display:flex;gap:6px;">
            <a href="?edit=<?= $c['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i></a>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
              <button class="btn btn-danger btn-sm" data-confirm="Delete this category?"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="catModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3><?= $editCat ? 'Edit' : 'Add' ?> Category</h3>
      <button onclick="this.closest('.modal-overlay').classList.remove('open')" style="background:none;border:none;color:var(--text2);font-size:1.2rem;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="save_category" value="1">
      <input type="hidden" name="edit_id" value="<?= $editCat['id'] ?? 0 ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Name *</label>
          <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editCat['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editCat['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="active" <?= ($editCat['status'] ?? 'active')==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= ($editCat['status'] ?? '')==='inactive'?'selected':'' ?>>Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="this.closest('.modal-overlay').classList.remove('open')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>
<?php if ($editCat) echo "<script>document.getElementById('catModal').classList.add('open');</script>"; ?>
<?php include 'admin-footer.php'; ?>
