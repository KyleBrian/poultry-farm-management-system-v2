<?php
/**
 * File: auth/register.php
 * User registration functionality
 * @version 1.0.1
 * @integration_verification PMSFV-004
 */
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/functions.php';

// Test database connection
$db_connected = test_db_connection();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$errors = [];
$success = false;
$form_data = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => '',
];

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone'] ?? '');
    
    // Save form data for repopulating the form
    $form_data = [
        'username' => $username,
        'email' => $email,
        'full_name' => $full_name,
        'phone' => $phone,
    ];
    
    // Validate input
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters and can only contain letters, numbers, and underscores";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    // Check if username or email already exists
    if (empty($errors) && $db_connected) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $errors[] = "Username or email already exists";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
        }
    }
    
    // Register user if no errors
    if (empty($errors) && $db_connected) {
        try {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, email, full_name, phone, role, status)
                VALUES (?, ?, ?, ?, ?, 'staff', 'active')
            ");
            
            $stmt->execute([$username, $password_hash, $email, $full_name, $phone]);
            
            // Set success message
            $success = true;
            
            // Clear form data
            $form_data = [
                'username' => '',
                'email' => '',
                'full_name' => '',
                'phone' => '',
            ];
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

// Page title
$page_title = "Register";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Poultry Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-primary-600 py-4 px-6">
            <h2 class="text-2xl font-bold text-white text-center">Poultry Management System</h2>
        </div>
        
        <div class="p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 text-center">Create an Account</h3>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                    <ul class="list-disc pl-5">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    <p>Registration successful! You can now <a href="login.php" class="font-bold underline">login</a> with your credentials.</p>
                </div>
            <?php else: ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-4">
                        <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-user text-gray-400"></i>
                            </span>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_data['username']); ?>" 
                                   class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" 
                                   placeholder="Choose a username" required>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">3-20 characters, letters, numbers, and underscores only</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </span>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" 
                                   class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" 
                                   placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="full_name" class="block text-gray-700 text-sm font-bold mb-2">Full Name *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-id-card text-gray-400"></i>
                            </span>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($form_data['full_name']); ?>" 
                                   class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" 
                                   placeholder="Enter your full name" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-phone text-gray-400"></i>
                            </span>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($form_data['phone']); ?>" 
                                   class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" 
                                   placeholder="Enter your phone number">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-lock text-gray-400"></i>
                            </span>
                            <input type="password" id="password" name="password" 
                                   class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" 
                                   placeholder="Create a password" required>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                    </div>
                    
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm Password *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-lock text-gray-400"></i>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" 
                                   placeholder="Confirm your password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                        Register
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">Already have an account? <a href="login.php" class="text-primary-600 hover:text-primary-500 font-medium">Login here</a></p>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

