<?php
/**
 * File: modules/egg_production/index.php
 * Egg production dashboard
 * @version 1.0.1
 * @integration_verification PMSFV-037
 */

// Include required files
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Page title
$page_title = "Egg Production Management";

// Get active flocks for the dropdown
$active_flocks = db_query($pdo, "
    SELECT id, flock_id, batch_name 
    FROM flocks 
    WHERE status = 'active' 
    ORDER BY acquisition_date DESC
");

// Default date range (last 7 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));

// Filter by date range if provided
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = sanitize_input($_GET['start_date']);
    $end_date = sanitize_input($_GET['end_date']);
}

// Filter by flock if provided
$flock_filter = "";
$selected_flock = "";
if (isset($_GET['flock_id']) && !empty($_GET['flock_id'])) {
    $selected_flock = sanitize_input($_GET['flock_id']);
    $flock_filter = "AND ep.flock_id = :flock_id";
}

// Get egg production data
$params = [':start_date' => $start_date, ':end_date' => $end_date];
if (!empty($selected_flock)) {
    $params[':flock_id'] = $selected_flock;
}

$egg_production = db_query($pdo, "
    SELECT 
        ep.id,
        ep.collection_date,
        f.flock_id,
        f.batch_name,
        ep.total_eggs,
        ep.broken_eggs,
        ep.small_eggs,
        ep.medium_eggs,
        ep.large_eggs,
        ep.xlarge_eggs,
        (ep.total_eggs - ep.broken_eggs) as good_eggs,
        ep.notes
    FROM 
        egg_production ep
    JOIN 
        flocks f ON ep.flock_id = f.id
    WHERE 
        ep.collection_date BETWEEN :start_date AND :end_date
        $flock_filter
    ORDER BY 
        ep.collection_date DESC
", $params);

// Get production summary
$production_summary = db_query($pdo, "
    SELECT 
        SUM(total_eggs) as total_collected,
        SUM(broken_eggs) as total_broken,
        SUM(small_eggs) as total_small,
        SUM(medium_eggs) as total_medium,
        SUM(large_eggs) as total_large,
        SUM(xlarge_eggs) as total_xlarge,
        SUM(total_eggs - broken_eggs) as total_good,
        ROUND(AVG(total_eggs), 2) as avg_daily_collection
    FROM 
        egg_production
    WHERE 
        collection_date BETWEEN :start_date AND :end_date
        $flock_filter
", $params);

// Get chart data
$chart_data = db_query($pdo, "
    SELECT 
        collection_date,
        SUM(total_eggs) as total_eggs,
        SUM(broken_eggs) as broken_eggs,
        SUM(total_eggs - broken_eggs) as good_eggs
    FROM 
        egg_production
    WHERE 
        collection_date BETWEEN :start_date AND :end_date
        $flock_filter
    GROUP BY 
        collection_date
    ORDER BY 
        collection_date ASC
", $params);

// Format chart data for JavaScript
$dates = [];
$total_eggs = [];
$good_eggs = [];

foreach ($chart_data as $data) {
    $dates[] = date('M d', strtotime($data['collection_date']));
    $total_eggs[] = $data['total_eggs'];
    $good_eggs[] = $data['good_eggs'];
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
            <li class="breadcrumb-item active">Egg Production</li>
        </ol>

        <!-- Page Content -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-egg me-1"></i>
                Egg Production Management
                <a href="<?php echo BASE_URL; ?>modules/egg_production/add.php" class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-plus"></i> Record New Production
                </a>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="flock_id" class="form-label">Flock</label>
                            <select class="form-select" id="flock_id" name="flock_id">
                                <option value="">All Flocks</option>
                                <?php foreach ($active_flocks as $flock): ?>
                                    <option value="<?php echo $flock['id']; ?>" <?php echo ($selected_flock == $flock['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($flock['flock_id'] . ' - ' . $flock['batch_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>
                </form>

                <!-- Production Summary -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-primary text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Total Eggs</h6>
                                        <h2 class="mb-0"><?php echo number_format($production_summary[0]['total_collected']); ?></h2>
                                    </div>
                                    <i class="fas fa-egg fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-success text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Good Eggs</h6>
                                        <h2 class="mb-0"><?php echo number_format($production_summary[0]['total_good']); ?></h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-danger text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Broken Eggs</h6>
                                        <h2 class="mb-0"><?php echo number_format($production_summary[0]['total_broken']); ?></h2>
                                    </div>
                                    <i class="fas fa-times-circle fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-info text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Avg. Daily Collection</h6>
                                        <h2 class="mb-0"><?php echo number_format($production_summary[0]['avg_daily_collection']); ?></h2>
                                    </div>
                                    <i class="fas fa-chart-line fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Production Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-1"></i>
                        Egg Production Trend
                    </div>
                    <div class="card-body">
                        <canvas id="eggProductionChart" width="100%" height="30"></canvas>
                    </div>
                </div>

                <!-- Production Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="eggProductionTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Flock</th>
                                <th>Total Eggs</th>
                                <th>Good Eggs</th>
                                <th>Broken</th>
                                <th>Small</th>
                                <th>Medium</th>
                                <th>Large</th>
                                <th>X-Large</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($egg_production) > 0): ?>
                                <?php foreach ($egg_production as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['collection_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['flock_id'] . ' - ' . $record['batch_name']); ?></td>
                                        <td><?php echo number_format($record['total_eggs']); ?></td>
                                        <td><?php echo number_format($record['good_eggs']); ?></td>
                                        <td><?php echo number_format($record['broken_eggs']); ?></td>
                                        <td><?php echo number_format($record['small_eggs']); ?></td>
                                        <td><?php echo number_format($record['medium_eggs']); ?></td>
                                        <td><?php echo number_format($record['large_eggs']); ?></td>
                                        <td><?php echo number_format($record['xlarge_eggs']); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>modules/egg_production/view.php?id=<?php echo $record['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>modules/egg_production/edit.php?id=<?php echo $record['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $record['id']; ?>)" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">No egg production records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this egg production record? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Page specific scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#eggProductionTable').DataTable({
            order: [[0, 'desc']]
        });
    });

    // Delete confirmation
    function confirmDelete(id) {
        $('#confirmDeleteBtn').attr('href', '<?php echo BASE_URL; ?>modules/egg_production/delete.php?id=' + id);
        $('#deleteModal').modal('show');
    }

    // Production Chart
    var ctx = document.getElementById('eggProductionChart').getContext('2d');
    var eggProductionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [
                {
                    label: 'Total Eggs',
                    data: <?php echo json_encode($total_eggs); ?>,
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 2,
                    tension: 0.1
                },
                {
                    label: 'Good Eggs',
                    data: <?php echo json_encode($good_eggs); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

