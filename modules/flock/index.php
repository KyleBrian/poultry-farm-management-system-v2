<?php
/**
 * File: modules/flock/index.php
 * Flock management module for the Poultry Farm Management System
 * @version 1.0.2
 */

// Include configuration
require_once '../../config/config.php';

// Require authentication
require_auth();

// Set page title
$page_title = 'Flock Management';

// Get flocks
$flocks = db_query($pdo, "
    SELECT f.*, 
           COALESCE(SUM(fdr.mortality), 0) as total_mortality,
           COALESCE(SUM(fdr.culls), 0) as total_culls,
           (f.quantity - COALESCE(SUM(fdr.mortality), 0) - COALESCE(SUM(fdr.culls), 0)) as current_count
    FROM flocks f
    LEFT JOIN flock_daily_records fdr ON f.id = fdr.flock_id
    GROUP BY f.id
    ORDER BY f.acquisition_date DESC
");

// Include header
include_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Flock Management</h1>
        <div>
            <a href="add.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add New Flock
            </a>
            <a href="daily_records.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-clipboard-list fa-sm text-white-50"></i> Daily Records
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Flocks</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Flock ID</th>
                            <th>Breed</th>
                            <th>Batch Name</th>
                            <th>Initial Quantity</th>
                            <th>Current Count</th>
                            <th>Acquisition Date</th>
                            <th>Age (Days)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flocks as $flock): ?>
                            <?php 
                                // Calculate age in days
                                $acquisition_date = new DateTime($flock['acquisition_date']);
                                $today = new DateTime('today');
                                $age_days = $acquisition_date->diff($today)->days + $flock['acquisition_age'];
                                
                                // Determine status class
                                $status_class = '';
                                switch ($flock['status']) {
                                    case 'active':
                                        $status_class = 'success';
                                        break;
                                    case 'sold':
                                        $status_class = 'info';
                                        break;
                                    case 'culled':
                                        $status_class = 'warning';
                                        break;
                                    case 'completed':
                                        $status_class = 'secondary';
                                        break;
                                }
                            ?>
                            <tr>
                                <td><?php echo $flock['flock_id']; ?></td>
                                <td><?php echo $flock['breed']; ?></td>
                                <td><?php echo $flock['batch_name']; ?></td>
                                <td><?php echo number_format($flock['quantity']); ?></td>
                                <td><?php echo number_format($flock['current_count']); ?></td>
                                <td><?php echo format_date($flock['acquisition_date']); ?></td>
                                <td><?php echo $age_days; ?></td>
                                <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($flock['status']); ?></span></td>
                                <td>
                                    <a href="view.php?id=<?php echo $flock['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $flock['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="add_record.php?flock_id=<?php echo $flock['id']; ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-clipboard"></i>
                                    </a>
                                    <?php if ($flock['status'] == 'active'): ?>
                                        <a href="change_status.php?id=<?php echo $flock['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-exchange-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>

