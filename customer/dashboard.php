<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAuth();

// Get customer's company name
$companyName = $_SESSION['company_name'] ?? '';

// Handle search
$shipments = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['cust_po']) || isset($_GET['comment'])) {
    $custPo = $_POST['cust_po'] ?? $_GET['cust_po'] ?? '';
    $comment = $_POST['comment'] ?? $_GET['comment'] ?? '';
    
    $query = "SELECT * FROM shipments WHERE ship_to_name = :company_name";
    $params = [':company_name' => $companyName];
    
    if (!empty($custPo)) {
        $query .= " AND cust_po LIKE :cust_po";
        $params[':cust_po'] = "%$custPo%";
    }
    
    if (!empty($comment)) {
        $query .= " AND comment LIKE :comment";
        $params[':comment'] = "%$comment%";
    }
    
    $query .= " ORDER BY trans_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $shipments = $stmt->fetchAll();
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv' && !empty($shipments)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="shipments_export.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, [
        'Invoice #', 'Invoice Date', 'Trans. Date', 'Cust. PO #', 'Ship Via',
        'Comment', 'Item Code', 'Description', 'Qty. Ordered', 'Qty. Shipped',
        'Qty. Backorder', 'PRO NUMBER'
    ]);
    
    // Data rows
    foreach ($shipments as $shipment) {
        fputcsv($output, [
            $shipment['invoice_number'],
            $shipment['invoice_date'],
            $shipment['trans_date'],
            $shipment['cust_po'],
            $shipment['ship_via'],
            $shipment['comment'],
            $shipment['item_code'],
            $shipment['description'],
            $shipment['qty_ordered'],
            $shipment['qty_shipped'],
            $shipment['qty_backorder'],
            $shipment['pro_number']
        ]);
    }
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | Shipping Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <span class="text-xl font-bold text-blue-600">Shipping Hub</span>
                        </div>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="ml-3 relative">
                            <div>
                                <button type="button" class="bg-white rounded-full flex text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="user-menu-button">
                                    <span class="sr-only">Open user menu</span>
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="ml-2 text-gray-700"><?= htmlspecialchars($companyName) ?></span>
                                </button>
                            </div>
                            <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden" id="user-menu">
                                <a href="../includes/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="container mx-auto px-4 py-8">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Welcome, <?= htmlspecialchars($companyName) ?></h1>
                
                <div class="bg-white shadow rounded-lg overflow-hidden mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Search Shipments</h2>
                    </div>
                    <div class="p-6">
                        <form method="post" class="space-y-4">
                            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                                <div>
                                    <label for="cust_po" class="block text-sm font-medium text-gray-700">Purchase Order #</label>
                                    <input type="text" name="cust_po" id="cust_po" 
                                           value="<?= htmlspecialchars($_POST['cust_po'] ?? '') ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                
                                <div>
                                    <label for="comment" class="block text-sm font-medium text-gray-700">Comment Contains</label>
                                    <input type="text" name="comment" id="comment" 
                                           value="<?= htmlspecialchars($_POST['comment'] ?? '') ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3">
                                <button type="reset" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Reset
                                </button>
                                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (!empty($shipments)): ?>
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">Search Results</h2>
                        <a href="?<?= http_build_query([
                            'cust_po' => $_POST['cust_po'] ?? $_GET['cust_po'] ?? '',
                            'comment' => $_POST['comment'] ?? $_GET['comment'] ?? '',
                            'export' => 'csv'
                        ]) ?>" 
                           class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-5 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-file-export mr-2"></i> Export to CSV
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO #</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ship Via</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($shipments as $shipment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-blue-600"><?= htmlspecialchars($shipment['invoice_number']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= date('M d, Y', strtotime($shipment['trans_date'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($shipment['cust_po'] ?? 'N/A') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($shipment['ship_via'] ?? 'N/A') ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($shipment['description']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($shipment['item_code']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($shipment['qty_shipped'] == $shipment['qty_ordered']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Shipped (<?= $shipment['qty_shipped'] ?>/<?= $shipment['qty_ordered'] ?>)
                                        </span>
                                        <?php elseif ($shipment['qty_shipped'] > 0): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Partial (<?= $shipment['qty_shipped'] ?>/<?= $shipment['qty_ordered'] ?>)
                                        </span>
                                        <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Pending (0/<?= $shipment['qty_ordered'] ?>)
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-6 text-center">
                        <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No shipments found</h3>
                        <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle user menu
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        
        if (userMenuButton && userMenu) {
            userMenuButton.addEventListener('click', function() {
                userMenu.classList.toggle('hidden');
            });
        }
    });
    </script>
</body>
</html>