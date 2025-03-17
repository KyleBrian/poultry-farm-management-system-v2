<?php
/**
 * File: auth/login.php
 * User authentication - login functionality
 * @version 1.0.2
 * @integration_verification PMSFV-003
 */
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php'; // Use centralized session management
require_once '../config/functions.php';

// Test database connection
$db_connected = test_db_connection();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to the page they were trying to access, or dashboard if none
    $redirect = isset($_SESSION['login_redirect']) ? $_SESSION['login_redirect'] : BASE_URL . "dashboard.php";
    unset($_SESSION['login_redirect']);
    header("Location: " . $redirect);
    exit();
}

$error = '';
$username = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } elseif (!$db_connected) {
        $error = "Database connection error. Please contact the administrator.";
    } else {
        try {
            // Check user credentials
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Log login activity
                log_activity($pdo, $user['id'], 'login', 'User logged in');
                
                // Update last login time
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Redirect to the page they were trying to access, or dashboard if none
                $redirect = isset($_SESSION['login_redirect']) ? $_SESSION['login_redirect'] : BASE_URL . "dashboard.php";
                unset($_SESSION['login_redirect']);
                header("Location: " . $redirect);
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Page title
$page_title = "Login";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Poultry Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-primary-600 py-4 px-6">
            <h2 class="text-2xl font-bold text-white text-center">Poultry Management System</h2>
        </div>
        
        <div class="p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 text-center">Login to Your Account</h3>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username or Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-user text-gray-400"></i>
                        </span>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" 
                               class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" 
                               placeholder="Enter your username or email" required>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-lock text-gray-400"></i>
                        </span>
                        <input type="password" id="password" name="password" 
                               class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" 
                               placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="text-sm text-primary-600 hover:text-primary-500">Forgot password?</a>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Sign In
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">Don't have an account? <a href="register.php" class="text-primary-600 hover:text-primary-500 font-medium">Register here</a></p>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

