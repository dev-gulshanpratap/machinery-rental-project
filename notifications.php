<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Notifications';
$db = getDB();
$uid = $_SESSION['user_id'];

// Mark all as read
$db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);

$notifications = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC");
$notifications->execute([$uid]);
$notifications = $notifications->fetchAll();

$typeIcons = ['success'=>'check-circle','danger'=>'exclamation-circle','warning'=>'exclamation-triangle','info'=>'info-circle'];
?>
<?php include '../includes/header.php'; ?>
<main class="main-content">
<div class="page-header">
  <div class="container"><h1>Notifications</h1></div>
</div>
<div class="container section-sm">
  <div style="max-width:700px;margin:0 auto;">
    <?php if (empty($notifications)): ?>
    <div class="empty-state"><i class="fas fa-bell"></i><h3>No Notifications</h3></div>
    <?php else: ?>
    <div class="panel">
      <div class="panel-body" style="padding:0;">
        <?php foreach ($notifications as $n): ?>
        <div style="display:flex;gap:14px;padding:16px 20px;border-bottom:1px solid var(--border);align-items:flex-start;">
          <div style="width:36px;height:36px;border-radius:50%;background:rgba(245,158,11,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-<?= $typeIcons[$n['type']] ?? 'bell' ?>" style="color:var(--accent);font-size:0.9rem;"></i>
          </div>
          <div style="flex:1;">
            <div style="font-weight:600;margin-bottom:3px;"><?= htmlspecialchars($n['title']) ?></div>
            <div style="font-size:0.85rem;color:var(--text2);"><?= htmlspecialchars($n['message']) ?></div>
            <div style="font-size:0.72rem;color:var(--text3);margin-top:5px;"><?= date('M d, Y g:i A', strtotime($n['created_at'])) ?></div>
          </div>
          <?php if ($n['link']): ?>
          <a href="<?= $n['link'] ?>" class="btn btn-outline btn-sm">View</a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
</main>
<?php include '../includes/footer.php'; ?>
