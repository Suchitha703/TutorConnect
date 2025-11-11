<?php
require_once 'includes/data.php';
require_once 'includes/auth.php';

$db = new Database();
$auth = new Auth($db->conn);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $user = $auth->login($email, $password);
        
        // Redirect based on user type
        if ($user['user_type'] === 'student') {
            header("Location: student/dashboard.php");
        } else {
            header("Location: tutor/dashboard.php");
        }
        exit();
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
    <title>Login | LocalTutorConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden w-full max-w-md">
            <div class="bg-blue-600 text-white p-6 text-center">
                <h1 class="text-2xl font-bold">Welcome Back</h1>
                <p class="mt-2">Login to continue to your account</p>
            </div>
            
            <div class="p-6">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Email Address</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="remember" class="form-checkbox text-blue-600">
                            <span class="ml-2">Remember me</span>
                        </label>
                        <a href="#" class="text-blue-600 hover:underline">Forgot password?</a>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-300">
                            Login
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-gray-600">Don't have an account? <a href="register.php" class="text-blue-600 hover:underline">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>