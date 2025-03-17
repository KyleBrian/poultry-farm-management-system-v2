<?php
// Start session
session_start();

// Include configuration and functions
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
$success = false;
$form_data = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => '',
    'farm_name' => '',
    'farm_size' => '',
    'farm_location' => '',
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
    $farm_name = trim($_POST['farm_name'] ?? '');
    $farm_size = trim($_POST['farm_size'] ?? '');
    $farm_location = trim($_POST['farm_location'] ?? '');
    
    // Save form data for repopulating the form
    $form_data = [
        'username' => $username,
        'email' => $email,
        'full_name' => $full_name,
        'phone' => $phone,
        'farm_name' => $farm_name,
        'farm_size' => $farm_size,
        'farm_location' => $farm_location,
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
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
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
    if (empty($errors)) {
        try {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin transaction
            $pdo->beginTransaction();
            
            // Insert user into database
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, email, full_name, phone, role, status)
                VALUES (?, ?, ?, ?, ?, 'user', 'active')
            ");
            
            $stmt->execute([$username, $password_hash, $email, $full_name, $phone]);
            $user_id = $pdo->lastInsertId();
            
            // Insert farm details if provided
            if (!empty($farm_name)) {
                $stmt = $pdo->prepare("
                    INSERT INTO farms (user_id, farm_name, farm_size, location)
                    VALUES (?, ?, ?, ?)
                ");
                
                $stmt->execute([$user_id, $farm_name, $farm_size, $farm_location]);
            }
            
            // Log activity
            log_activity($pdo, $user_id, 'registration', 'New user registered: ' . $username);
            
            // Commit transaction
            $pdo->commit();
            
            // Set success message
            $success = true;
            
            // Clear form data
            $form_data = [
                'username' => '',
                'email' => '',
                'full_name' => '',
                'phone' => '',
                'farm_name' => '',
                'farm_size' => '',
                'farm_location' => '',
            ];
            
        } catch (PDOException $e) {
            // Rollback transaction
            $pdo->rollBack();
            
            $errors[] = "Database error: " . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

// Get selected plan from query string
$selected_plan = isset($_GET['plan']) ? $_GET['plan'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PFMS</title>
    <!-- Favicon -->
    <link rel="icon" href="assets/img/favicon.png" type="image/png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-page">
    <!-- Preloader -->
    <div id="preloader">
        <div class="spinner"></div>
    </div>

    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="auth-card auth-card-lg animate__animated animate__fadeInDown">
                <div class="auth-header">
                    <a href="index.php" class="auth-logo">
                        <img src="assets/img/logo-dark.png" alt="PFMS Logo" height="40">
                        <span>PFMS</span>
                    </a>
                    <h1 class="auth-title">Create an Account</h1>
                    <p class="auth-subtitle">Join PFMS to manage your poultry farm efficiently</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i> Registration successful! You can now <a href="login.php" class="alert-link">login</a> with your credentials.
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="auth-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($form_data['username']); ?>" 
                                               placeholder="Choose a username" required>
                                    </div>
                                    <small class="form-text text-muted">3-20 characters, letters, numbers, and underscores only</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($form_data['email']); ?>" 
                                               placeholder="Enter your email" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Create a password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Minimum 6 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               placeholder="Confirm your password" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($form_data['full_name']); ?>" 
                                               placeholder="Enter your full name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($form_data['phone']); ?>" 
                                               placeholder="Enter your phone number">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="farm-details mb-4">
                            <h4 class="farm-details-title">Farm Details (Optional)</h4>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label for="farm_name" class="form-label">Farm Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-home"></i></span>
                                            <input type="text" class="form-control" id="farm_name" name="farm_name" 
                                                   value="<?php echo htmlspecialchars($form_data['farm_name']); ?>" 
                                                   placeholder="Enter your farm name">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label for="farm_size" class="form-label">Farm Size (Number of Birds)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                                            <input type="number" class="form-control" id="farm_size" name="farm_size" 
                                                   value="<?php echo htmlspecialchars($form_data['farm_size']); ?>" 
                                                   placeholder="Enter farm size">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="farm_location" class="form-label">Farm Location</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" class="form-control" id="farm_location" name="farm_location" 
                                           value="<?php echo htmlspecialchars($form_data['farm_location']); ?>" 
                                           placeholder="Enter farm location">
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($selected_plan)): ?>
                            <div class="selected-plan mb-4">
                                <h4 class="selected-plan-title">Selected Plan</h4>
                                <div class="plan-badge <?php echo $selected_plan; ?>">
                                    <?php echo ucfirst($selected_plan); ?> Plan
                                </div>
                                <input type="hidden" name="selected_plan" value="<?php echo htmlspecialchars($selected_plan); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus me-2"></i> Register
                        </button>
                    </form>
                <?php endif; ?>
                
                <div class="auth-divider">
                    <span>or</span>
                </div>
                
                <div class="social-login">
                    <button class="btn btn-google">
                        <i class="fab fa-google me-2"></i> Register with Google
                    </button>
                    <button class="btn btn-facebook">
                        <i class="fab fa-facebook-f me-2"></i> Register with Facebook
                    </button>
                </div>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms of Service Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4>1. Acceptance of Terms</h4>
                    <p>By accessing or using the Poultry Farm Management System (PFMS), you agree to be bound by these Terms of Service.</p>
                    
                    <h4>2. Description of Service</h4>
                    <p>PFMS provides a web-based platform for managing poultry farm operations, including but not limited to flock management, egg production tracking, feed management, and financial reporting.</p>
                    
                    <h4>3. User Accounts</h4>
                    <p>You are responsible for maintaining the confidentiality of your account information and password. You agree to accept responsibility for all activities that occur under your account.</p>
                    
                    <h4>4. Data Privacy</h4>
                    <p>We collect and process your data in accordance with our Privacy Policy. By using PFMS, you consent to such processing and you warrant that all data provided by you is accurate.</p>
                    
                    <h4>5. Termination</h4>
                    <p>We reserve the right to terminate or suspend your account and access to PFMS at our sole discretion, without notice, for conduct that we believe violates these Terms of Service or is harmful to other users of PFMS, us, or third parties, or for any other reason.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4>1. Information We Collect</h4>
                    <p>We collect information you provide directly to us, such as your name, email address, phone number, and farm details. We also collect information about your use of PFMS.</p>
                    
                    <h4>2. How We Use Your Information</h4>
                    <p>We use the information we collect to provide, maintain, and improve PFMS, to communicate with you, and to comply with legal obligations.</p>
                    
                    <h4>3. Information Sharing</h4>
                    <p>We do not share your personal information with third parties except as described in this Privacy Policy.</p>
                    
                    <h4>4. Data Security</h4>
                    <p>We take reasonable measures to help protect your personal information from loss, theft, misuse, unauthorized access, disclosure, alteration, and destruction.</p>
                    
                    <h4>5. Your Rights</h4>
                    <p>You have the right to access, update, or delete your personal information. You can do this by contacting us or using the account settings in PFMS.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Toggle password visibility
        $('.toggle-password').click(function() {
            const passwordField = $(this).siblings('input');
            const passwordFieldType = passwordField.attr('type');
            
            if (passwordFieldType === 'password') {
                passwordField.attr('type', 'text');
                $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    </script>
</body>
</html>

