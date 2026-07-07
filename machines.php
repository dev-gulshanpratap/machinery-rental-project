<?php
require_once '../includes/config.php';
$pageTitle = 'Browse Machines';
$db = getDB();

// Filters
$catId    = (int)($_GET['category'] ?? 0);
$status   = sanitize($_GET['status'] ?? '');
$search   = sanitize($_GET['q'] ?? '');
$sort     = sanitize($_GET['sort'] ?? 'newest');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// Build query
$where = ['1=1'];
$params = [];

if ($catId) { $where[] = 'm.category_id = ?'; $params[] = $catId; }
if ($status) { $where[] = 'm.status = ?'; $params[] = $status; }
if ($search) { $where[] = '(m.name LIKE ? OR m.model LIKE ? OR m.manufacturer LIKE ?)'; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }

$whereStr = implode(' AND ', $where);
$orderBy = match($sort) {
    'price_asc'  => 'm.daily_rate ASC',
    'price_desc' => 'm.daily_rate DESC',
    'rating'     => 'm.condition_rating DESC',
    default      => 'm.id DESC',
};

$countStmt = $db->prepare("SELECT COUNT(*) FROM machines m WHERE $whereStr");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $db->prepare("SELECT m.*, c.name as cat_name FROM machines m JOIN categories c ON m.category_id=c.id WHERE $whereStr ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$machines = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<main class="main-content">

<div class="page-header">
  <div class="container">
    <div class="breadcrumb">
      <a href="<?= SITE_URL ?>">Home</a>
      <span class="sep">/</span>
      <span class="current">Machines</span>
    </div>
    <h1>Browse Machines</h1>
    <p><?= $total ?> machines available for rent</p>
  </div>
</div>

<div class="container section-sm">
  <!-- Filter Bar -->
  <form method="GET" class="filter-bar">
    <div class="filter-group" style="flex:2;">
      <label>Search</label>
      <div class="filter-search">
        <input type="text" name="q" class="form-control" placeholder="Machine name, model, brand..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
      </div>
    </div>
    <div class="filter-group">
      <label>Category</label>
      <select name="category" class="form-control">
        <option value="">All Categories</option>
        <?php foreach($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label>Status</label>
      <select name="status" class="form-control">
        <option value="">All Status</option>
        <option value="available" <?= $status==='available' ? 'selected' : '' ?>>Available</option>
        <option value="rented" <?= $status==='rented' ? 'selected' : '' ?>>Rented</option>
        <option value="maintenance" <?= $status==='maintenance' ? 'selected' : '' ?>>Maintenance</option>
      </select>
    </div>
    <div class="filter-group">
      <label>Sort By</label>
      <select name="sort" class="form-control">
        <option value="newest" <?= $sort==='newest' ? 'selected' : '' ?>>Newest First</option>
        <option value="price_asc" <?= $sort==='price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
        <option value="price_desc" <?= $sort==='price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
        <option value="rating" <?= $sort==='rating' ? 'selected' : '' ?>>Best Condition</option>
      </select>
    </div>
  </form>

  <?php if (empty($machines)): ?>
  <div class="empty-state">
    <i class="fas fa-tractor"></i>
    <h3>No Machines Found</h3>
    <p>Try adjusting your filters or search query.</p>
    <a href="machines.php" class="btn btn-outline mt-2">Clear Filters</a>
  </div>
  <?php else: ?>

  <div class="grid-4">
    <?php foreach ($machines as $m): ?>
    <div class="card fade-in">
      <div class="card-img"><i class="fas fa-tractor"></i></div>
      <div class="card-body">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
          <span class="status-badge status-<?= $m['status'] ?>"><?= ucfirst($m['status']) ?></span>
          <span style="font-size:0.78rem;color:var(--text3);"><i class="fas fa-star text-accent"></i> <?= $m['condition_rating'] ?>/10</span>
        </div>
        <div class="card-title"><?= htmlspecialchars($m['name']) ?></div>
        <div class="card-meta">
          <span><i class="fas fa-industry"></i> <?= htmlspecialchars($m['cat_name']) ?></span>
          <span><i class="fas fa-cog"></i> <?= htmlspecialchars($m['model'] ?? 'N/A') ?></span>
          <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($m['location']) ?></span>
        </div>
        <div class="card-price">
          <span class="price-amount"><?= formatCurrency($m['daily_rate']) ?></span>
          <span class="price-unit">/ day</span>
        </div>
      </div>
      <div class="card-footer">
        <span style="font-size:0.78rem;color:var(--text3);"><?= $m['manufacturer'] ?> <?= $m['year_of_manufacture'] ?></span>
        <a href="machine-detail.php?id=<?= $m['id'] ?>" class="btn btn-primary btn-sm">Details</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>">← Prev</a><?php endif; ?>
    <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
    <<?= $i==$page ? 'span class="current"' : 'a href="?'.http_build_query(array_merge($_GET,['page'=>$i])).'"' ?>><?= $i ?></<?= $i==$page ? 'span' : 'a' ?>>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>">Next →</a><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
</main>
<?php include '../includes/footer.php'; ?>
