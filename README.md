# 🏭 MachineryRent — Industrial Machinery Rental System
### Complete PHP + XAMPP Project

---

## 📋 SETUP INSTRUCTIONS

### Step 1: Copy to XAMPP
```
Copy the `machinery-rental` folder into:
C:\xampp\htdocs\machinery-rental\     (Windows)
/Applications/XAMPP/htdocs/machinery-rental/   (Mac)
```

### Step 2: Start XAMPP
- Open XAMPP Control Panel
- Start **Apache** and **MySQL**

### Step 3: Create Database
1. Open `http://localhost/phpmyadmin`
2. Click **New** → Database name: `machinery_rental` → Create
3. Click **Import** tab → Choose `database.sql` → Import

### Step 4: Open the Website
- **Frontend:** `http://localhost/machinery-rental/`
- **Admin Panel:** `http://localhost/machinery-rental/admin/`

### Step 5: Login
| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@machineryrent.com | Admin@123 |
| Register new customer account | - | - |

---

## 📁 PROJECT STRUCTURE

```
machinery-rental/
├── index.php               ← Homepage
├── login.php               ← Login page
├── register.php            ← Registration
├── logout.php              ← Logout
├── database.sql            ← Database schema + seed data
│
├── includes/
│   ├── config.php          ← DB config + helper functions
│   ├── header.php          ← Public header/navbar
│   └── footer.php          ← Public footer
│
├── pages/                  ← Customer-facing pages
│   ├── machines.php        ← Browse all machines
│   ├── machine-detail.php  ← Machine detail + book form
│   ├── dashboard.php       ← Customer dashboard
│   ├── my-rentals.php      ← View rental requests
│   ├── invoices.php        ← View/print invoices
│   ├── profile.php         ← Edit profile/password
│   └── notifications.php   ← Notification center
│
├── admin/                  ← Admin panel
│   ├── index.php           ← Admin dashboard
│   ├── rental-requests.php ← Approve/reject rentals
│   ├── machines.php        ← Add/edit/delete machines
│   ├── categories.php      ← Manage categories
│   ├── maintenance.php     ← Maintenance tracking
│   ├── invoices.php        ← All invoices
│   ├── customers.php       ← Customer management
│   ├── admin-header.php    ← Admin header/sidebar
│   └── admin-footer.php    ← Admin footer
│
├── css/
│   ├── main.css            ← Main stylesheet
│   └── admin.css           ← Admin overrides
│
└── js/
    └── main.js             ← JavaScript
```

---

## ✅ FEATURES IMPLEMENTED

### Customer Side
- ✅ Register & Login with secure password hashing
- ✅ Browse machines with search + category + status filters
- ✅ Machine detail page with specs and ratings
- ✅ Rental request form with live price calculator
- ✅ Customer dashboard with stats
- ✅ My Rentals — view all requests and status
- ✅ Invoice view + print functionality
- ✅ Notification system
- ✅ Profile management

### Admin Panel
- ✅ Dashboard with revenue + machine stats
- ✅ Rental Request approval / rejection with reason
- ✅ Auto-generate invoice on approval
- ✅ Machine CRUD (add/edit/delete)
- ✅ Category management
- ✅ Maintenance tracking with priority levels
- ✅ Customer management (activate/deactivate)
- ✅ Invoice management

### Technical
- ✅ PDO with prepared statements (SQL injection safe)
- ✅ Password hashing with `password_hash()`
- ✅ Session-based authentication
- ✅ Role-based access (admin / customer)
- ✅ Flash messages system
- ✅ Notification system
- ✅ GST invoice calculation (18%)
- ✅ Responsive design

---

## 🔒 SECURITY FEATURES
- Prepared statements prevent SQL injection
- `password_hash()` + `password_verify()` for passwords
- `htmlspecialchars()` everywhere to prevent XSS
- Session-based auth guards on all protected pages
- Admin-only access control

---

## 💡 CUSTOMIZATION

### Change Currency Symbol
In `includes/config.php`:
```php
define('CURRENCY', '₹');  // Change to $ or € etc.
```

### Change Tax Rate
```php
define('TAX_RATE', 18);   // Change GST %
```

### Change Site URL
```php
define('SITE_URL', 'http://localhost/machinery-rental');
```
