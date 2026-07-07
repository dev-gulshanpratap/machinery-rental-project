<?php
// ============================================================
// DATABASE CONFIGURATION
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'machinery_rental');

define('SITE_NAME', 'MachineryRent');
define('SITE_URL', 'http://localhost/machinery-rental');
define('CURRENCY', '₹');
define('TAX_RATE', 18); // GST %
define('ADMIN_EMAIL', 'admin@machineryrent.com');

// Connect using PDO
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: Redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper: Is Logged In
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper: Is Admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Helper: Auth Guard
function requireLogin() {
    if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
}

function requireAdmin() {
    if (!isAdmin()) redirect(SITE_URL . '/index.php');
}

// Helper: Generate unique IDs
function generateRequestNumber() {
    return 'REQ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generateInvoiceNumber() {
    return 'INV-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Helper: Format Currency
function formatCurrency($amount) {
    return CURRENCY . ' ' . number_format($amount, 2);
}

// Helper: Flash Messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Helper: Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Helper: Add Notification
function addNotification($userId, $title, $message, $type = 'info', $link = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $title, $message, $type, $link]);
}
