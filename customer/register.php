<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = trim($_POST['company_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    try {
        // Validate inputs
        if (empty($companyName) || empty($email) || empty($password)) {
            throw new Exception('All fields are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }

        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Email already registered');
        }

        // Create customer
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

        // Auto-login after registration
        if (loginUser($email, $password)) {
            header("Location: dashboard.php");
            exit();
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Shipping Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .register-bg {
            background-image: url('https://images.pexels.com/photos/4483610/pexels-photo-4483610.jpeg');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex">
        <!-- Left side with background image -->
        <div class="hidden lg:block lg:w-1/2 register-bg"></div>
        
        <!-- Right side with registration form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-12">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Shipping Hub</h1>
                    <p class="mt-2 text-gray-600">Create your account</p>
                </div>
                
                <?php if ($error): ?>
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
                
                <div class="bg-white rounded-lg shadow-md p-8">
                    <form method="post" class="space-y-6">
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                            <div class="mt-1">
                                <input id="company_name" name="company_name" type="text" required
                                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                            <div class="mt-1">
                                <input id="email" name="email" type="email" required
                                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <div class="mt-1">
                                <input id="password" name="password" type="password" required minlength="8"
                                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">At least 8 characters</p>
                            </div>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <div class="mt-1">
                                <input id="confirm_password" name="confirm_password" type="password" required minlength="8"
                                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>
                        
                        <div>
                            <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Register
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Already have an account?</span>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <a href="login.php"
                               class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Sign in
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>