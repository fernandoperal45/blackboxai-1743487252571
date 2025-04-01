<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Get dashboard metrics
$metrics = [
    'total_shipments' => $pdo->query("SELECT COUNT(*) FROM shipments")->fetchColumn(),
    'total_customers' => $pdo->query("SELECT COUNT(*) FROM customers WHERE role = 'customer'")->fetchColumn(),
    'active_carriers' => $pdo->query("SELECT COUNT(DISTINCT ship_via) FROM shipments")->fetchColumn(),
    'backordered_items' => $pdo->query("SELECT SUM(qty_backorder) FROM shipments WHERE qty_backorder > 0")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <?php include '../includes/admin_nav.php'; ?>
        
        <main class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Admin Dashboard</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Metrics Cards -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-truck text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Shipments</p>
                            <h3 class="text-2xl font-bold"><?= number_format($metrics['total_shipments']) ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Customers</p>
                            <h3 class="text-2xl font-bold"><?= number_format($metrics['total_customers']) ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                            <i class="fas fa-shipping-fast text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500">Active Carriers</p>
                            <h3 class="text-2xl font-bold"><?= number_format($metrics['active_carriers']) ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500">Backordered Items</p>
                            <h3 class="text-2xl font-bold"><?= number_format($metrics['backordered_items']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
                    <div class="space-y-3">
                        <a href="upload.php" class="flex items-center p-3 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition">
                            <i class="fas fa-file-upload mr-3"></i>
                            <span>Upload Shipment Data</span>
                        </a>
                        <a href="manage_customers.php" class="flex items-center p-3 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition">
                            <i class="fas fa-user-cog mr-3"></i>
                            <span>Manage Customers</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Recent Shipments</h2>
                    <?php
                    $recentShipments = $pdo->query("
                        SELECT invoice_number, ship_to_name, trans_date 
                        FROM shipments 
                        ORDER BY trans_date DESC 
                        LIMIT 5
                    ")->fetchAll();
                    ?>
                    <div class="space-y-3">
                        <?php foreach ($recentShipments as $shipment): ?>
                        <div class="flex justify-between items-center p-3 border-b border-gray-100">
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($shipment['invoice_number']) ?></p>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($shipment['ship_to_name']) ?></p>
                            </div>
                            <span class="text-sm text-gray-500"><?= date('M d, Y', strtotime($shipment['trans_date'])) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>