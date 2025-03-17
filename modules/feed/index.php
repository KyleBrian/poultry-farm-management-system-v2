<?php
/**
 * File: modules/feed/index.php
 * Feed management main page
 * @version 1.0.3
 * @integration_verification PMSFV-015
 */
// Include required files
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php'; // Use centralized session management
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Page title
$page_title = "Feed Management";

// Get feed inventory summary
$feed_inventory = db_query($pdo, "
    SELECT 
        feed_type,
        SUM(quantity) as total_quantity,
        AVG(unit_price) as avg_price,
        MIN(expiry_date) as nearest_expiry
    FROM feed_inventory
    WHERE status = 'in_stock'
    GROUP BY feed_type
    ORDER BY feed_type
");

// Get recent feed consumption
$recent_consumption = db_query($pdo, "
    SELECT 
        fc.consumption_date,
        f.flock_id,
        f.batch_name,
        fi.feed_type,
        fc.quantity,
        fc.notes
    FROM 
        feed_consumption fc
    JOIN 
        flocks f ON fc.flock_id = f.id
    JOIN 
        feed_inventory fi ON fc.feed_inventory_id = fi.id
    ORDER BY 
        fc.consumption_date DESC
    LIMIT 10
");

// Get feed consumption by type (for chart)
$consumption_by_type = db_query($pdo, "
    SELECT 
        fi.feed_type,
        SUM(fc.quantity) as total_consumed
    FROM 
        feed_consumption fc
    JOIN 
        feed_inventory fi ON fc.feed_inventory_id = fi.id
    WHERE 
        fc.consumption_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY 
        fi.feed_type
    ORDER BY 
        total_consumed DESC
");

// Get feed consumption by flock (for chart)
$consumption_by_flock = db_query($pdo, "
    SELECT 
        f.batch_name,
        SUM(fc.quantity) as total_consumed
    FROM 
        feed_consumption fc
    JOIN 
        flocks f ON fc.flock_id = f.id
    WHERE 
        fc.consumption_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY 
        f.id
    ORDER BY 
        total_consumed DESC
    LIMIT 5
");

// Get low stock alerts
$low_stock_alerts = db_query($pdo, "
    SELECT 
        feed_type,
        SUM(quantity) as total_quantity
    FROM 
        feed_inventory
    WHERE 
        status = 'in_stock'
    GROUP BY 
        feed_type
    HAVING 
        total_quantity < 100
    ORDER BY 
        total_quantity ASC
");

// Format chart data
$feed_types = [];
$consumption_amounts = [];
foreach ($consumption_by_type as $item) {
    $feed_types[] = $item['feed_type'];
    $consumption_amounts[] = $item['total_consumed'];
}

$flock_names = [];
$flock_consumption = [];
foreach ($consumption_by_flock as $item) {
    $flock_names[] = $item['batch_name'];
    $flock_consumption[] = $item['total_consumed'];
}

// Include header
include '../../includes/header.php';
?>

<!-- Main Content -->
<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo BASE_URL; ?>dashboard.php">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Feed Management</li>
        </ol>

        <!-- Page Content -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-wheat-alt me-1"></i>
                            Feed Management Dashboard
                        </div>
                        <div>
                            <a href="<?php echo BASE_URL; ?>modules/feed/add_inventory.php" class="btn btn-success btn-sm me-2">
                                <i class="fas fa-plus"></i> Add Feed Inventory
                            </a>
                            <a href="<?php echo BASE_URL; ?>modules/feed/record_consumption.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-utensils"></i> Record Consumption
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Feed Inventory Summary -->
                        <div class="row mb-4">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-warehouse me-1"></i>
                                        Current Feed Inventory
                                        <a href="<?php echo BASE_URL; ?>modules/feed/inventory.php" class="btn btn-sm btn-outline-primary float-end">
                                            View All
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Feed Type</th>
                                                        <th>Available Quantity (kg)</th>
                                                        <th>Average Price</th>
                                                        <th>Nearest Expiry</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($feed_inventory) > 0): ?>
                                                        <?php foreach ($feed_inventory as $item): ?>
                                                            <?php 
                                                                $expiry_days = (strtotime($item['nearest_expiry']) - time()) / (60 * 60 * 24);
                                                                $status_class = 'success';
                                                                $status_text = 'Good';
                                                                
                                                                if ($item['total_quantity'] < 100) {
                                                                    $status_class = 'danger';
                                                                    $status_text = 'Low Stock';
                                                                } elseif ($expiry_days < 30) {
                                                                    $status_class = 'warning';
                                                                    $status_text = 'Expiring Soon';
                                                                }
                                                            ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($item['feed_type']); ?></td>
                                                                <td><?php echo number_format($item['total_quantity'], 2); ?></td>
                                                                <td><?php echo CURRENCY_SYMBOL . number_format($item['avg_price'], 2); ?></td>
                                                                <td><?php echo date('M d, Y', strtotime($item['nearest_expiry'])); ?></td>
                                                                <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="5" class="text-center">No feed inventory found</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Feed Consumption Charts -->
                        <div class="row mb-4">
                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-chart-pie me-1"></i>
                                        Feed Consumption by Type (Last 30 Days)
                                    </div>
                                    <div class="card-body">
                                        <canvas id="feedTypeChart" width="100%" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i>
                                        Feed Consumption by Flock (Last 30 Days)
                                    </div>
                                    <div class="card-body">
                                        <canvas id="flockConsumptionChart" width="100%" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Feed Consumption -->
                        <div class="row">
                            <div class="col-xl-8">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-history me-1"></i>
                                        Recent Feed Consumption
                                        <a href="<?php echo BASE_URL; ?>modules/feed/consumption_records.php" class="btn btn-sm btn-outline-primary float-end">
                                            View All
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Flock</th>
                                                        <th>Feed Type</th>
                                                        <th>Quantity (kg)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($recent_consumption) > 0): ?>
                                                        <?php foreach ($recent_consumption as $item): ?>
                                                            <tr>
                                                                <td><?php echo date('M d, Y', strtotime($item['consumption_date'])); ?></td>
                                                                <td><?php echo htmlspecialchars($item['flock_id'] . ' - ' . $item['batch_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($item['feed_type']); ?></td>
                                                                <td><?php echo number_format($item['quantity'], 2); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center">No recent feed consumption records</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="card">
                                    <div class="card-header bg-danger text-white">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Low Stock Alerts
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($low_stock_alerts) > 0): ?>
                                            <ul class="list-group">
                                                <?php foreach ($low_stock_alerts as $alert): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <?php echo htmlspecialchars($alert['feed_type']); ?>
                                                        <span class="badge bg-danger rounded-pill"><?php echo number_format($alert['total_quantity'], 2); ?> kg</span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <div class="mt-3">
                                                <a href="<?php echo BASE_URL; ?>modules/feed/add_inventory.php" class="btn btn-danger btn-sm w-100">
                                                    <i class="fas fa-plus"></i> Add Inventory
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-center mb-0">No low stock alerts at this time.</p>
                                        <?php endif; ?>
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

<!-- Page specific scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Feed Type Chart
    var feedTypeCtx = document.getElementById('feedTypeChart').getContext('2d');
    var feedTypeChart = new Chart(feedTypeCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($feed_types); ?>,
            datasets: [{
                data: <?php echo json_encode($consumption_amounts); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });

    // Flock Consumption Chart
    var flockCtx = document.getElementById('flockConsumptionChart').getContext('2d');
    var flockConsumptionChart = new Chart(flockCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($flock_names); ?>,
            datasets: [{
                label: 'Feed Consumed (kg)',
                data: <?php echo json_encode($flock_consumption); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

