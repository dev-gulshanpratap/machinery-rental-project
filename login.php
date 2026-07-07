<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect(SITE_URL . '/pages/dashboard.php');
$pageTitle = 'Login';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect($user['role'] === 'admin' ? SITE_URL . '/admin/index.php' : SITE_URL . '/pages/dashboard.php');
        } else {
            $errors[] = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | MachineryRent</title>
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
      <h1 style="font-size:2.8rem;font-weight:900;margin-bottom:16px;">India's #1<br>Industrial Machinery<br><span style="color:var(--accent);">Rental Platform</span></h1>
      <p style="color:var(--text2);font-size:1rem;line-height:1.7;max-width:380px;">Rent premium heavy equipment from 12+ cities. Fast approval, transparent pricing, and 24/7 support.</p>
      <div style="margin-top:48px;display:flex;flex-direction:column;gap:16px;">
        <?php $features = [['fas fa-check-circle','500+ Premium Machines'],['fas fa-check-circle','Fast 24-hour Approval'],['fas fa-check-circle','GST Invoicing'],['fas fa-check-circle','Nationwide Delivery']];
        foreach($features as $f): ?>
        <div style="display:flex;align-items:center;gap:12px;color:var(--text2);">
          <i class="<?= $f[0] ?>" style="color:var(--green);"></i> <?= $f[1] ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-box">
      <div class="section-label">Welcome Back</div>
      <h2>Sign In to Your Account</h2>
      <p class="subtitle">Don't have an account? <a href="register.php" style="color:var(--accent);">Register free</a></p>

      <?php if ($errors): ?>
      <div style="background:#2d0a0a;border:1px solid var(--red);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:0.88rem;color:var(--red);">
        <i class="fas fa-exclamation-circle"></i> <?= implode('<br>', $errors) ?>
      </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="your@company.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <div style="position:relative;">
            <input type="password" name="password" id="pwdField" class="form-control" placeholder="••••••••" required>
            <button type="button" onclick="togglePwd()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text3);cursor:pointer;"><i class="fas fa-eye" id="eyeIcon"></i></button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg mt-2">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
      </form>

      <div style="margin-top:24px;padding:16px;background:var(--surface);border-radius:8px;font-size:0.82rem;color:var(--text2);">
        <strong style="color:var(--accent);">Demo Credentials:</strong><br>
        Admin: admin@machineryrent.com / Admin@123
      </div>
    </div>
  </div>
</div>
<script>
function togglePwd(){
  const f=document.getElementById('pwdField');
  const e=document.getElementById('eyeIcon');
  if(f.type==='password'){f.type='text';e.className='fas fa-eye-slash';}
  else{f.type='password';e.className='fas fa-eye';}
}
</script>
</body>
</html>
