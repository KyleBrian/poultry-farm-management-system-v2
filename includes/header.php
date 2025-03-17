<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    // Use the renamed function
    $user = get_logged_in_user();
}

// Check if user has access to the current page
$current_page = basename($_SERVER['PHP_SELF']);
$restricted_pages = ['dashboard.php', 'birds.php', 'feed.php', 'eggs.php', 'sales.php', 'employees.php', 'reports.php'];

if (in_array($current_page, $restricted_pages) && !$user) {
    // Redirect to login page if not logged in and trying to access restricted page
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <script src="<?php echo APP_URL; ?>/assets/js/jquery.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/fontawesome.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #4a7c59;
            --secondary-color: #f8b400;
            --accent-color: #e67e22;
            --light-color: #f5f5f5;
            --dark-color: #333;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background-color: var(--primary-color);
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.25rem;
            border-radius: 0.25rem;
            margin-bottom: 0.25rem;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.2);
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        
        .content-wrapper {
            min-height: 100vh;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #3a6347;
            border-color: #3a6347;
        }
        
        .table th {
            font-weight: 600;
            background-color: rgba(0,0,0,0.02);
        }
    </style>
</head>
<body>
<?php if ($user): ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4><?php echo APP_NAME; ?></h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'birds.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/birds/index.php">
                                <i class="fas fa-feather-alt"></i> Birds Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'feed.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/feed/index.php">
                                <i class="fas fa-wheat-awn"></i> Feed Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'eggs.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/egg_production/index.php">
                                <i class="fas fa-egg"></i> Egg Production
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'health.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/health/index.php">
                                <i class="fas fa-stethoscope"></i> Health Records
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'sales.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/sales/index.php">
                                <i class="fas fa-shopping-cart"></i> Sales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/customers/index.php">
                                <i class="fas fa-users"></i> Customers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'employees.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/employees/index.php">
                                <i class="fas fa-user-tie"></i> Employees
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'expenses.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/expenses/index.php">
                                <i class="fas fa-money-bill"></i> Expenses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/reports/index.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="<?php echo APP_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-wrapper">
                <nav class="navbar navbar-expand-lg navbar-light">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target=".sidebar">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-2"><i class="fas fa-user-circle"></i></span>
                            <span>Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                        </div>
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-bell"></i>
                                    <span class="badge bg-danger rounded-pill">3</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#">Low feed stock alert</a></li>
                                    <li><a class="dropdown-item" href="#">Health check reminder</a></li>
                                    <li><a class="dropdown-item" href="#">New order received</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                
                <div class="container-fluid py-4">
<?php else: ?>
    <!-- Non-dashboard header for login/register pages -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--primary-color);">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>/index.php"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/index.php">Home</a>
                    </li>
                    <?php if ($current_page == 'login.php'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/register.php">Register</a>
                        </li>
                    <?php elseif ($current_page == 'register.php'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container py-4">
<?php endif; ?>

