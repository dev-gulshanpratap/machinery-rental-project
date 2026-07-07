<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'My Profile';
$db = getDB();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $company = sanitize($_POST['company_name'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    $db->prepare("UPDATE users SET name=?,phone=?,company_name=?,address=? WHERE id=?")->execute([$name,$phone,$company,$address,$uid]);
    $_SESSION['name'] = $name;

    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            setFlash('danger','Passwords do not match.');
        } else {
            $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $uid]);
            setFlash('success','Profile and password updated!');
        }
    } else {
        setFlash('success','Profile updated!');
    }
    redirect(SITE_URL.'/pages/profile.php');
}

$user = $db->prepare("SELECT * FROM users WHERE id=?"); $user->execute([$uid]); $user = $user->fetch();
?>
<?php include '../includes/header.php'; ?>
<main class="main-content">
<div class="page-header">
  <div class="container">
    <h1>My Profile</h1>
  </div>
</div>
<div class="container section-sm">
  <div style="max-width:600px;margin:0 auto;">
    <div class="panel">
      <div class="panel-header"><h3>Account Information</h3></div>
      <div class="panel-body">
        <form method="POST">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email (cannot change)</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
          </div>
          <div class="form-group">
            <label class="form-label">Company Name</label>
            <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($user['company_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
          </div>
          <hr style="border-color:var(--border);margin:20px 0;">
          <h4 style="margin-bottom:16px;">Change Password (optional)</h4>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
            </div>
            <div class="form-group">
              <label class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-save"></i> Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>
</main>
<?php include '../includes/footer.php'; ?>
