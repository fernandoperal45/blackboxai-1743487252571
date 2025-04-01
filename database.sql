-- Create shipments table
CREATE TABLE IF NOT EXISTS shipments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL,
    invoice_date DATE NOT NULL,
    trans_date DATE NOT NULL,
    cust_po VARCHAR(50),
    ship_via VARCHAR(50),
    comment TEXT,
    ship_to_name VARCHAR(100) NOT NULL,
    item_code VARCHAR(50) NOT NULL,
    description VARCHAR(200) NOT NULL,
    qty_ordered INT NOT NULL DEFAULT 0,
    qty_shipped INT NOT NULL DEFAULT 0,
    qty_backorder INT NOT NULL DEFAULT 0,
    pro_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cust_po (cust_po),
    INDEX idx_comment (comment(255)),
    INDEX idx_ship_to (ship_to_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create admin user (default credentials: admin@example.com / Admin@123)
INSERT IGNORE INTO customers (company_name, email, password_hash, role, is_active)
VALUES ('Admin Company', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE);