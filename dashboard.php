<?php
// Start session
session_start();

// Include configuration and functions
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get dashboard statistics
// Flocks
$stmt = $pdo->query("SELECT COUNT(*) FROM flocks WHERE status = 'active'");
$active_flocks = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT SUM(quantity) FROM flocks WHERE status = 'active'");
$total_birds = $stmt->fetchColumn() ?: 0;

// Calculate mortality rate
$stmt = $pdo->query("
    SELECT 
        (SUM(mortality) / (SELECT SUM(quantity) FROM flocks WHERE status = 'active')) * 100 as mortality_rate
    FROM flock_daily_records
    WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$mortality_rate = $stmt->fetchColumn() ?: 0;

// Egg Production
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT SUM(total_eggs) FROM egg_production WHERE collection_date = ?");
$stmt->execute([$today]);
$eggs_today = $stmt->fetchColumn() ?: 0;

$this_month = date('Y-m');
$stmt = $pdo->prepare("SELECT SUM(total_eggs) FROM egg_production WHERE DATE_FORMAT(collection_date, '%Y-%m') = ?");
$stmt->execute([$this_month]);
$eggs_this_month = $stmt->fetchColumn() ?: 0;

// Feed
$stmt = $pdo->query("SELECT SUM(quantity) FROM feed_inventory");
$feed_stock = $stmt->fetchColumn() ?: 0;

// Employees
$stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
$total_employees = $stmt->fetchColumn() ?: 0;

// Financial
$stmt = $pdo->prepare("
    SELECT SUM(amount) FROM expenses 
    WHERE expense_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
");
$stmt->execute();
$expenses_this_month = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("
    SELECT SUM(total_amount) FROM sales 
    WHERE sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
");
$stmt->execute();
$sales_this_month = $stmt->fetchColumn() ?: 0;

// Get egg production data for chart (last 7 days)
$egg_chart_data = [];
$egg_chart_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT SUM(total_eggs) FROM egg_production WHERE collection_date = ?");
    $stmt->execute([$date]);
    $eggs = $stmt->fetchColumn() ?: 0;
    
    $egg_chart_data[] = $eggs;
    $egg_chart_labels[] = date('M d', strtotime($date));
}

// Get feed consumption data for chart (last 7 days)
$feed_chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("
        SELECT SUM(quantity) FROM feed_consumption 
        WHERE consumption_date = ?
    ");
    $stmt->execute([$date]);
    $feed = $stmt->fetchColumn() ?: 0;
    
    $feed_chart_data[] = $feed;
}

// Get payroll data for chart
$stmt = $pdo->query("
    SELECT 
        position, 
        AVG(salary) as avg_salary,
        COUNT(*) as count
    FROM employees
    WHERE status = 'active'
    GROUP BY position
");
$payroll_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$payroll_labels = [];
$payroll_data_values = [];
$payroll_colors = [];
$payroll_percentages = [];

$total_employees_for_payroll = 0;
foreach ($payroll_data as $data) {
    $total_employees_for_payroll += $data['count'];
}

$color_palette = [
    '#4e73df', // Blue
    '#e74a3b', // Red
    '#1cc88a', // Green
    '#f6c23e', // Yellow
    '#36b9cc', // Cyan
    '#6f42c1', // Purple
    '#fd7e14', // Orange
    '#20c9a6', // Teal
    '#e83e8c', // Pink
    '#6610f2'  // Indigo
];

$i = 0;
foreach ($payroll_data as $data) {
    $payroll_labels[] = $data['position'];
    $payroll_data_values[] = $data['avg_salary'];
    $payroll_colors[] = $color_palette[$i % count($color_palette)];
    $payroll_percentages[] = ($data['count'] / $total_employees_for_payroll) * 100;
    $i++;
}

// Recent Activities
$stmt = $pdo->prepare("
    SELECT a.*, u.username 
    FROM activity_log a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$page_title = "Dashboard";

// Include header
include 'includes/header.php';
?>

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <?php include 'includes/topnav.php'; ?>
        
        <!-- Dashboard Content -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="content-title">Dashboard</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content">
                <div class="container-fluid">
                    <!-- Welcome Message -->
                    <div class="welcome-message">
                        <h2>Hello, <?php echo htmlspecialchars($user['full_name']); ?>.</h2>
                        <p>Welcome to your dashboard</p>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row">
                        <!-- Birds Stats -->
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-primary">
                                        <i class="fas fa-kiwi-bird"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">No. of Birds</div>
                                        <div class="stats-card-value"><?php echo number_format($total_birds); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mortality Rate -->
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-danger">
                                        <i class="fas fa-skull"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">Mortality Rate</div>
                                        <div class="stats-card-value"><?php echo number_format($mortality_rate, 1); ?>%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Eggs Stats -->
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-success">
                                        <i class="fas fa-egg"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">No. of Eggs</div>
                                        <div class="stats-card-value"><?php echo number_format($eggs_today); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Employees Stats -->
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-info">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">No. of Employees</div>
                                        <div class="stats-card-value"><?php echo number_format($total_employees); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row">
                        <!-- Egg Production Chart -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Egg Production (Last 7 Days)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="eggProductionChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Feed Consumption Chart -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Feed Consumption (Last 7 Days)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="feedConsumptionChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payroll and Stats Row -->
                    <div class="row">
                        <!-- Payroll Visualization -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Payroll Visualization</h5>
                                    <div class="card-subtitle">Job titles and their respective salaries</div>
                                </div>
                                <div class="card-body">
                                    <div class="payroll-chart-container">
                                        <canvas id="payrollChart" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stats -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Stats</h5>
                                    <div class="card-subtitle">Statistics of different categories</div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="stats-box bg-light-blue">
                                                <h4 class="stats-box-title">Total Wages</h4>
                                                <div class="stats-box-value"><?php echo format_currency($expenses_this_month); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="stats-box bg-light-green">
                                                <h4 class="stats-box-title">Sales</h4>
                                                <div class="stats-box-value"><?php echo format_currency($sales_this_month); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="stats-box bg-light-gray">
                                                <h4 class="stats-box-title">Remaining Feed</h4>
                                                <div class="stats-box-value"><?php echo number_format($feed_stock); ?> Kg</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="stats-box bg-light-pink">
                                                <h4 class="stats-box-title">Eggs Left</h4>
                                                <div class="stats-box-value"><?php echo number_format($eggs_today); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activities -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Recent Activities</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Activity</th>
                                                    <th>Description</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recent_activities)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No recent activities found.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_activities as $activity): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="user-info">
                                                                    <span class="user-avatar">
                                                                        <i class="fas fa-user"></i>
                                                                    </span>
                                                                    <span class="user-name"><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="activity-badge <?php echo $activity['activity_type']; ?>">
                                                                    <?php echo htmlspecialchars(ucfirst($activity['activity_type'])); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                                            <td><?php echo format_datetime($activity['created_at']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="row">
                        <div class="col-12">
                            <div class="quick-links">
                                <h5 class="quick-links-title">Quick Links</h5>
                                <div class="row">
                                    <div class="col-md-3 col-sm-6">
                                        <a href="modules/flock/index.php" class="quick-link-card">
                                            <div class="quick-link-icon bg-primary">
                                                <i class="fas fa-feather-alt"></i>
                                            </div>
                                            <div class="quick-link-content">
                                                <h4 class="quick-link-title">Manage Flocks</h4>
                                                <p class="quick-link-text">View and manage bird flocks</p>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="modules/egg_production/index.php" class="quick-link-card">
                                            <div class="quick-link-icon bg-success">
                                                <i class="fas fa-egg"></i>
                                            </div>
                                            <div class="quick-link-content">
                                                <h4 class="quick-link-title">Egg Production</h4>
                                                <p class="quick-link-text">Record and track egg production</p>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="modules/feed/index.php" class="quick-link-card">
                                            <div class="quick-link-icon bg-warning">
                                                <i class="fas fa-wheat-awn"></i>
                                            </div>
                                            <div class="quick-link-content">
                                                <h4 class="quick-link-title">Feed Management</h4>
                                                <p class="quick-link-text">Manage feed inventory and consumption</p>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="modules/reports/index.php" class="quick-link-card">
                                            <div class="quick-link-icon bg-info">
                                                <i class="fas fa-chart-bar"></i>
                                            </div>
                                            <div class="quick-link-content">
                                                <h4 class="quick-link-title">Reports</h4>
                                                <p class="quick-link-text">Generate and view reports</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Egg Production Chart
var eggCtx = document.getElementById('eggProductionChart').getContext('2d');
var eggChart = new Chart(eggCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($egg_chart_labels); ?>,
        datasets: [{
            label: 'Eggs Collected',
            data: <?php echo json_encode($egg_chart_data); ?>,
            backgroundColor: 'rgba(40, 167, 69, 0.2)',
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Eggs'
                }
            }
        }
    }
});

// Feed Consumption Chart
var feedCtx = document.getElementById('feedConsumptionChart').getContext('2d');
var feedChart = new Chart(feedCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($egg_chart_labels); ?>,
        datasets: [{
            label: 'Feed Used (kg)',
            data: <?php echo json_encode($feed_chart_data); ?>,
            backgroundColor: 'rgba(255, 193, 7, 0.2)',
            borderColor: 'rgba(255, 193, 7, 1)',
            borderWidth: 2,
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Feed (kg)'
                }
            }
        }
    }
});

// Payroll Chart
var payrollCtx = document.getElementById('payrollChart').getContext('2d');
var payrollChart = new Chart(payrollCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($payroll_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($payroll_percentages); ?>,
            backgroundColor: <?php echo json_encode($payroll_colors); ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 15,
                    padding: 15
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var label = context.label || '';
                        var value = context.raw || 0;
                        return label + ': ' + value.toFixed(1) + '%';
                    }
                }
            }
        }
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>

