<?php
require_once 'includes/config.php';

try {
    // Read SQL file
    $sql = file_get_contents('database.sql');
    
    // Split into individual queries
    $queries = explode(';', $sql);
    
    // Execute each query
    foreach ($queries as $query) {
        if (trim($query) !== '') {
            $pdo->exec($query);
        }
    }
    
    echo "Database tables created successfully!";
    
    // Create initial admin user if not exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = 'admin@example.com'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $pdo->prepare("
            INSERT INTO customers 
            (company_name, email, password_hash, role, is_active)
            VALUES 
            ('Admin Company', 'admin@example.com', ?, 'admin', TRUE)
        ")->execute([password_hash('Admin@123', PASSWORD_BCRYPT)]);
        
        echo "\nDefault admin user created:\nEmail: admin@example.com\nPassword: Admin@123";
    }
    
} catch (PDOException $e) {
    die("Error setting up database: " . $e->getMessage());
}