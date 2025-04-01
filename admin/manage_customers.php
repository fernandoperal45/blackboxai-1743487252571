<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $companyName = $_POST['company_name'];
                    $email = $_POST['email'];
                    $password = $_POST['password'];
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO customers 
                        (company_name, email, password_hash, role, is_active)
                        VALUES (?, ?, ?, 'customer', TRUE)
                    ");
                    $stmt->execute([
                        $companyName,
                        $email,
                        password_hash($password, PASSWORD_BCRYPT)
                    ]);
                    $message = "Customer created successfully!";
                    break;
                    
                case 'toggle_status':
                    $customerId = $_POST['customer_id'];
                    $currentStatus = $_POST['current_status'];
                    
                    $stmt = $pdo->prepare("
                        UPDATE customers 
                        SET is_active = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([!$currentStatus, $customerId]);
                    $message = "Customer status updated!";
                    break;
                    
                case 'reset_password':
                    $customerId = $_POST['customer_id'];
                    $newPassword = bin2hex(random_bytes(4)); // Generate random password
                    
                    $stmt = $pdo->prepare("
                        UPDATE customers 
                        SET password_hash = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        password_hash($newPassword, PASSWORD_BCRYPT),
                        $customerId
                    ]);
                    $message = "Password reset to: $newPassword";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get all customers
$customers = $pdo->query("
    SELECT id, company_name, email, is_active, created_at 
    FROM customers 
    WHERE role = 'customer'
    ORDER BY company_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <?php include '../includes/admin_nav.php'; ?>
        
        <main class="container mx-auto px-4 py-8">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Customers</h1>
                
                <?php if (isset($message)): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?= htmlspecialchars($message) ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="bg-white shadow rounded-lg overflow-hidden mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Create New Customer</h2>
                    </div>
                    <div class="p-6">
                        <form method="post" class="space-y-4">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                <div class="sm:col-span-3">
                                    <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                                    <input type="text" name="company_name" id="company_name" required
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" name="email" id="email" required
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                    <input type="password" name="password" id="password" required minlength="8"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" 
                                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Create Customer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Customer List</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($customer['company_name']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($customer['email']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $customer['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $customer['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M d, Y', strtotime($customer['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form method="post" class="inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $customer['is_active'] ?>">
                                            <button type="submit" class="text-blue-600 hover:text-blue-900 mr-4">
                                                <?= $customer['is_active'] ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </form>
                                        
                                        <form method="post" class="inline">
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                Reset Password
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>