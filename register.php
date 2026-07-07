<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect(SITE_URL . '/pages/dashboard.php');
$pageTitle = 'Register';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $company  = sanitize($_POST['company_name'] ?? '');
    $address  = sanitize($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name))    $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();
        $exists = $db->prepare("SELECT id FROM users WHERE email = ?");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, phone, company_name, address, password, role) VALUES (?,?,?,?,?,?,'customer')");
            $stmt->execute([$name, $email, $phone, $company, $address, $hashed]);
            $newId = $db->lastInsertId();
            addNotification($newId, 'Welcome to MachineryRent!', 'Your account is ready. Start browsing machines.', 'success', SITE_URL . '/pages/machines.php');
            setFlash('success', 'Account created! Please log in.');
            redirect(SITE_URL . '/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | MachineryRent</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;900&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/css/main.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-left">
    <div style="position:relative;z-index:1;">
      <div class="nav-logo" style="margin-bottom:48px;font-size:1.6rem;">
        <div class="logo-icon"><i class="fas fa-industry"></i></div>
        <span class="logo-text">Machinery<strong>Rent</strong></span>
      </div>
      <h1 style="font-size:2.8rem;font-weight:900;margin-bottom:16px;">Start Renting<br>Heavy Machinery<br><span style="color:var(--accent);">In Minutes</span></h1>
      <p style="color:var(--text2);font-size:1rem;line-height:1.7;">Free account. No hidden charges. Instant access to 500+ industrial machines across India.</p>
    </div>
  </div>
  <div class="auth-right">
    <div class="auth-box" style="max-width:500px;">
      <div class="section-label">Get Started</div>
      <h2>Create Your Account</h2>
      <p class="subtitle">Already registered? <a href="login.php" style="color:var(--accent);">Sign in</a></p>

      <?php if ($errors): ?>
      <div style="background:#2d0a0a;border:1px solid var(--red);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:0.88rem;color:var(--red);">
        <?php foreach($errors as $e): ?><div><i class="fas fa-times-circle"></i> <?= $e ?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name <span class="required">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="Rajesh Kumar" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address <span class="required">*</span></label>
          <input type="email" name="email" class="form-control" placeholder="your@company.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Company Name</label>
          <input type="text" name="company_name" class="form-control" placeholder="Your Company Pvt Ltd" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Business Address</label>
          <textarea name="address" class="form-control" placeholder="Full address..." rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password <span class="required">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password <span class="required">*</span></label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg">
          <i class="fas fa-user-plus"></i> Create Account
        </button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
