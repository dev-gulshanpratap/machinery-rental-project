-- ============================================================
-- INDUSTRIAL MACHINERY RENTAL SYSTEM - DATABASE SCHEMA
-- ============================================================

CREATE DATABASE IF NOT EXISTS machinery_rental;
USE machinery_rental;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    company_name VARCHAR(150),
    address TEXT,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Machine Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Machines
CREATE TABLE machines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    model VARCHAR(100),
    manufacturer VARCHAR(100),
    year_of_manufacture YEAR,
    serial_number VARCHAR(100) UNIQUE,
    description TEXT,
    daily_rate DECIMAL(10,2) NOT NULL,
    weekly_rate DECIMAL(10,2),
    monthly_rate DECIMAL(10,2),
    deposit_amount DECIMAL(10,2) DEFAULT 0,
    capacity VARCHAR(100),
    weight VARCHAR(50),
    dimensions VARCHAR(100),
    fuel_type VARCHAR(50),
    horsepower VARCHAR(50),
    location VARCHAR(200),
    status ENUM('available','rented','maintenance','retired') DEFAULT 'available',
    condition_rating TINYINT DEFAULT 5 COMMENT '1-10 scale',
    total_rentals INT DEFAULT 0,
    images JSON,
    specifications JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Rental Requests
CREATE TABLE rental_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    machine_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    rental_days INT NOT NULL,
    purpose TEXT,
    site_address TEXT,
    operator_required TINYINT(1) DEFAULT 0,
    status ENUM('pending','approved','rejected','active','completed','cancelled') DEFAULT 'pending',
    rejection_reason TEXT,
    subtotal DECIMAL(10,2),
    tax_amount DECIMAL(10,2),
    deposit_amount DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    payment_status ENUM('pending','partial','paid','refunded') DEFAULT 'pending',
    admin_notes TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (machine_id) REFERENCES machines(id)
);

-- Invoices
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) UNIQUE NOT NULL,
    rental_request_id INT NOT NULL,
    user_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 18.00,
    tax_amount DECIMAL(10,2) NOT NULL,
    deposit_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    balance_due DECIMAL(10,2),
    payment_method VARCHAR(50),
    payment_date DATE,
    status ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_request_id) REFERENCES rental_requests(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Maintenance Records
CREATE TABLE maintenance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT NOT NULL,
    maintenance_type ENUM('routine','repair','inspection','emergency') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    technician_name VARCHAR(100),
    technician_contact VARCHAR(50),
    start_date DATE NOT NULL,
    end_date DATE,
    cost DECIMAL(10,2) DEFAULT 0,
    parts_used TEXT,
    status ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    next_maintenance_date DATE,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(id)
);

-- Reviews
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rental_request_id INT NOT NULL,
    user_id INT NOT NULL,
    machine_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_request_id) REFERENCES rental_requests(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (machine_id) REFERENCES machines(id)
);

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','danger') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin user (password: Admin@123)
INSERT INTO users (name, email, phone, password, company_name, role) VALUES
('Super Admin', 'admin@machineryrent.com', '+91-9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MachineryRent Ltd', 'admin');

-- Categories
INSERT INTO categories (name, slug, description, icon) VALUES
('Excavators', 'excavators', 'Heavy-duty digging and earthmoving machines', 'fa-tractor'),
('Cranes', 'cranes', 'Tower, mobile and overhead lifting cranes', 'fa-building'),
('Bulldozers', 'bulldozers', 'Powerful earthmoving and grading machines', 'fa-truck'),
('Forklifts', 'forklifts', 'Material handling and warehouse forklifts', 'fa-forklift'),
('Compactors', 'compactors', 'Soil and asphalt compaction equipment', 'fa-compress'),
('Concrete Mixers', 'concrete-mixers', 'Industrial concrete mixing equipment', 'fa-cog'),
('Aerial Lifts', 'aerial-lifts', 'Boom lifts, scissor lifts and man lifts', 'fa-arrow-up'),
('Generators', 'generators', 'Industrial power generation equipment', 'fa-bolt');

-- Sample Machines
INSERT INTO machines (category_id, name, model, manufacturer, year_of_manufacture, serial_number, description, daily_rate, weekly_rate, monthly_rate, deposit_amount, capacity, weight, fuel_type, horsepower, location, condition_rating) VALUES
(1, 'Hydraulic Excavator 320D', 'CAT 320D', 'Caterpillar', 2021, 'CAT320D-2021-001', '20-ton hydraulic excavator ideal for large construction sites', 8500.00, 52000.00, 180000.00, 50000.00, '20 Ton', '20,000 kg', 'Diesel', '148 HP', 'Mumbai Yard', 9),
(1, 'Mini Excavator PC50', 'PC50MR-2', 'Komatsu', 2022, 'KOM-PC50-2022-042', 'Compact 5-ton excavator for tight spaces and urban projects', 3500.00, 21000.00, 75000.00, 20000.00, '5 Ton', '5,200 kg', 'Diesel', '38 HP', 'Delhi Yard', 9),
(2, 'Mobile Crane 50T', 'RT50E', 'Grove', 2020, 'GRV-RT50-2020-007', '50-ton rough terrain crane with 38m boom', 15000.00, 95000.00, 350000.00, 100000.00, '50 Ton', '38,000 kg', 'Diesel', '260 HP', 'Mumbai Yard', 8),
(3, 'Bulldozer D6T', 'D6T XL', 'Caterpillar', 2021, 'CAT-D6T-2021-015', 'Medium-sized crawler bulldozer with ripper attachment', 9500.00, 58000.00, 200000.00, 60000.00, 'Medium', '18,200 kg', 'Diesel', '185 HP', 'Pune Yard', 8),
(4, 'Counterbalance Forklift', 'FD30NT', 'Toyota', 2022, 'TOY-FD30-2022-033', '3-ton diesel forklift for warehouse operations', 2500.00, 15000.00, 55000.00, 15000.00, '3 Ton', '4,500 kg', 'Diesel', '75 HP', 'Delhi Yard', 9),
(7, 'Boom Lift 60ft', 'S-60X', 'Skyjack', 2021, 'SKY-S60-2021-019', '60-foot telescopic boom lift for aerial work', 4500.00, 27000.00, 95000.00, 30000.00, '280 kg', '7,800 kg', 'Diesel', '75 HP', 'Bangalore Yard', 9),
(8, 'Generator 500 KVA', 'DG500', 'Cummins', 2022, 'CUM-DG500-2022-008', '500 KVA diesel generator for industrial power backup', 6000.00, 37000.00, 130000.00, 40000.00, '500 KVA', '5,200 kg', 'Diesel', '680 HP', 'Mumbai Yard', 10),
(5, 'Vibratory Roller', 'SD100DC', 'Volvo', 2021, 'VOL-SD100-2021-022', '10-ton double drum vibratory roller for road compaction', 5500.00, 33000.00, 115000.00, 35000.00, '10 Ton', '10,200 kg', 'Diesel', '130 HP', 'Chennai Yard', 8);
