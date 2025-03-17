<?php
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$flock_id = isset($_GET['flock_id']) ? intval($_GET['flock_id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT v.*, f.name as flock_name, f.batch_number, vt.name as vaccine_name, u.username as created_by 
          FROM vaccinations v 
          LEFT JOIN flocks f ON v.flock_id = f.id 
          LEFT JOIN vaccine_types vt ON v.vaccine_type_id = vt.id 
          LEFT JOIN users u ON v.created_by = u.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (f.name LIKE ? OR f.batch_number LIKE ? OR vt.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($flock_id > 0) {
    $query .= " AND v.flock_id = ?";
    $params[] = $flock_id;
    $types .= "i";
}

if (!empty($status)) {
    $query .= " AND v.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($date_from)) {
    $query .= " AND v.vaccination_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND v.vaccination_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY v.vaccination_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$vaccinations = [];
while ($row = $result->fetch_assoc()) {
    $vaccinations[] = $row;
}

// Get all flocks for filter dropdown
$flocks_query = "SELECT id, name, batch_number FROM flocks WHERE status = 'Active' ORDER BY name";
$flocks_result = $conn->query($flocks_query);
$flocks = [];
while ($row = $flocks_result->fetch_assoc()) {
    $flocks[] = $row;
}

// Process vaccination status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $vaccination_id = intval($_POST['vaccination_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    $update_query = "UPDATE vaccinations SET status = ?, notes = CONCAT(IFNULL(notes, ''), '\n', NOW(), ' - Status updated to ', ?, ' - ', ?), updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $new_status, $new_status, $notes, $vaccination_id);
    
    if ($stmt->execute()) {
        // Log activity
        $log_query = "INSERT INTO activity_logs (user_id, action, module, description, created_at) VALUES (?, 'update', 'vaccination', ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $description = "Updated vaccination status to $new_status for ID: $vaccination_id";
        $log_stmt->bind_param("is", $_SESSION['user_id'], $description);
        $log_stmt->execute();
        
        $_SESSION['success'] = "Vaccination status updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update vaccination status: " . $conn->error;
    }
    
    // Redirect to refresh the page
    header("Location: index.php");
    exit();
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Vaccination Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Vaccinations</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php include '../../includes/alerts.php'; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Vaccination Records</h3>
                            <div class="card-tools">
                                <a href="record.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Add Vaccination
                                </a>
                                <a href="schedule.php" class="btn btn-info btn-sm ml-2">
                                    <i class="fas fa-calendar-alt"></i> Vaccination Schedule
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <form method="get" action="">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <select class="form-control" name="flock_id">
                                                        <option value="">All Flocks</option>
                                                        <?php foreach ($flocks as $flock): ?>
                                                            <option value="<?php echo $flock['id']; ?>" <?php echo ($flock_id == $flock['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($flock['name'] . ' (' . $flock['batch_number'] . ')'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <select class="form-control" name="status">
                                                        <option value="">All Status</option>
                                                        <option value="Scheduled" <?php echo ($status == 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                                        <option value="Completed" <?php echo ($status == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="Missed" <?php echo ($status == 'Missed') ? 'selected' : ''; ?>>Missed</option>
                                                        <option value="Cancelled" <?php echo ($status == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <input type="date" class="form-control" name="date_from" placeholder="From Date" value="<?php echo htmlspecialchars($date_from); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <input type="date" class="form-control" name="date_to" placeholder="To Date" value="<?php echo htmlspecialchars($date_to); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    <i class="fas fa-search"></i> Filter
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Flock</th>
                                            <th>Vaccine</th>
                                            <th>Date</th>
                                            <th>Dosage</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($vaccinations) > 0): ?>
                                            <?php foreach ($vaccinations as $vaccination): ?>
                                                <tr>
                                                    <td><?php echo $vaccination['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($vaccination['flock_name'] . ' (' . $vaccination['batch_number'] . ')'); ?></td>
                                                    <td><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($vaccination['vaccination_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($vaccination['dosage']); ?></td>
                                                    <td><?php echo htmlspecialchars($vaccination['administration_method']); ?></td>
                                                    <td>
                                                        <span class="badge <?php 
                                                            echo ($vaccination['status'] == 'Completed') ? 'badge-success' : 
                                                                (($vaccination['status'] == 'Scheduled') ? 'badge-info' : 
                                                                (($vaccination['status'] == 'Missed') ? 'badge-warning' : 'badge-danger')); 
                                                        ?>">
                                                            <?php echo $vaccination['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($vaccination['created_by']); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="view.php?id=<?php echo $vaccination['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="edit.php?id=<?php echo $vaccination['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#statusModal<?php echo $vaccination['id']; ?>">
                                                                <i class="fas fa-sync-alt"></i>
                                                            </button>
                                                        </div>
                                                        
                                                        <!-- Status Update Modal -->
                                                        <div class="modal fade" id="statusModal<?php echo $vaccination['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel<?php echo $vaccination['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="statusModalLabel<?php echo $vaccination['id']; ?>">Update Vaccination Status</h5>
                                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                            <span aria-hidden="true">&times;</span>
                                                                        </button>
                                                                    </div>
                                                                    <form method="post" action="">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="vaccination_id" value="<?php echo $vaccination['id']; ?>">
                                                                            <div class="form-group">
                                                                                <label for="new_status<?php echo $vaccination['id']; ?>">New Status</label>
                                                                                <select class="form-control" id="new_status<?php echo $vaccination['id']; ?>" name="new_status" required>
                                                                                    <option value="Scheduled" <?php echo ($vaccination['status'] == 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                                                                    <option value="Completed" <?php echo ($vaccination['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                                                                    <option value="Missed" <?php echo ($vaccination['status'] == 'Missed') ? 'selected' : ''; ?>>Missed</option>
                                                                                    <option value="Cancelled" <?php echo ($vaccination['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="form-group">
                                                                                <label for="notes<?php echo $vaccination['id']; ?>">Notes</label>
                                                                                <textarea class="form-control" id="notes<?php echo $vaccination['id']; ?>" name="notes" rows="3"></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No vaccination records found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
require_once '../../includes/footer.php';
?>

