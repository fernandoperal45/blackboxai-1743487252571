<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$message = '';
$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (loginUser($email, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password";
    }
}

// Handle password recovery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'recover') {
    $email = $_POST['email'];
    
    try {
        $stmt = $pdo->prepare("SELECT id, company_name FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token (valid for 1 hour)
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $pdo->prepare("
                UPDATE customers 
                SET reset_token = ?, reset_expires = ? 
                WHERE id = ?
            ")->execute([$token, $expires, $user['id']]);
            
            // In a real application, you would send an email here
            $message = "Password reset link has been sent to your email";
        } else {
            $error = "No account found with that email";
        }
    } catch (PDOException $e) {
        $error = "Error processing your request";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login | Shipping Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .login-bg {
            background-image: url('https://images.pexels.com/photos/4483610/pexels-photo-4483610.jpeg');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex">
        <!-- Left side with background image -->
        <div class="hidden lg:block lg:w-1/2 login-bg"></div>
        
        <!-- Right side with login form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-12">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Shipping Hub</h1>
                    <p class="mt-2 text-gray-600">Customer Portal</p>
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
                
                <?php if ($message): ?>
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
                
                <!-- Login Form -->
                <div id="login-form" class="bg-white rounded-lg shadow-md p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Sign in to your account</h2>
                    
                    <form method="post" class="space-y-6">
                        <input type="hidden" name="action" value="login">
                        
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
                                <input id="password" name="password" type="password" required
                                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember-me" name="remember-me" type="checkbox"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="remember-me" class="ml-2 block text-sm text-gray-900">Remember me</label>
                            </div>
                            
                            <div class="text-sm">
                                <button type="button" onclick="showRecoveryForm()" class="font-medium text-blue-600 hover:text-blue-500">
                                    Forgot your password?
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Sign in
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or</span>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <a href="register.php"
                               class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Create a new account
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recovery Form (hidden by default) -->
                <div id="recovery-form" class="bg-white rounded-lg shadow-md p-8 hidden">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Reset your password</h2>
                    
                    <form method="post" class="space-y-6">
                        <input type="hidden" name="action" value="recover">
                        
                        <div>
                            <label for="recovery-email" class="block text-sm font-medium text-gray-700">Email address</label>
                            <div class="mt-1">
                                <input id="recovery-email" name="email" type="email" required
                                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <button type="button" onclick="showLoginForm()" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                Back to login
                            </button>
                            
                            <button type="submit"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Send reset link
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showRecoveryForm() {
        document.getElementById('login-form').classList.add('hidden');
        document.getElementById('recovery-form').classList.remove('hidden');
    }
    
    function showLoginForm() {
        document.getElementById('recovery-form').classList.add('hidden');
        document.getElementById('login-form').classList.remove('hidden');
    }
    </script>
</body>
</html>