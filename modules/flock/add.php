<?php
/**
 * File: modules/flock/add.php
 * Add new flock page for the Poultry Farm Management System
 * @version 1.0.2
 */

// Include configuration
require_once '../../config/config.php';

// Require authentication
require_auth();

// Set page title
$page_title = 'Add New Flock';

// Initialize variables
$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $breed = sanitize($_POST['breed']);
    $batch_name = sanitize($_POST['batch_name']);
    $quantity = (int)$_POST['quantity'];
    $acquisition_date = sanitize($_POST['acquisition_date']);
    $acquisition_age = (int)$_POST['acquisition_age'];
    $source = sanitize($_POST['source']);
    $cost = (float)$_POST['cost'];
    $notes = sanitize($_POST['notes']);
    
    // Validate form data
    if (empty($breed) || empty($batch_name) || empty($acquisition_date)) {
        $error = 'Please fill in all required fields.';
    } elseif ($quantity <= 0) {
        $error = 'Quantity must be greater than zero.';
    } elseif ($acquisition_age < 0) {
        $error = 'Acquisition age cannot be negative.';
    } elseif ($cost < 0) {
        $error = 'Cost cannot be negative.';
    } elseif (!is_valid_date($acquisition_date)) {
        $error = 'Invalid acquisition date.';
    } else {
        // Generate flock ID
        $flock_id = generate_unique_id('FL');
        
        // Insert flock
        $data = [
            'flock_id' => $flock_id,
            'breed' => $breed,
            'batch_name' => $batch_name,
            'quantity' => $quantity,
            'acquisition_date' => $acquisition_date,
            'acquisition_age' => $acquisition_age,
            'source' => $source,
            'cost' => $cost,
            'notes' => $notes,
            'status' => 'active',
            'created_by' => get_current_user_id()
        ];
        
        $flock_id = db_insert($pdo, 'flocks', $data);
        
        if ($flock_id) {
            // Log activity
            log_activity('flock_add', 'Added new flock: ' . $batch_name);
            
            // Set success message
            $success = 'Flock added successfully.';
            
            // Clear form data
            $breed = '';
            $batch_name = '';
            $quantity = '';
            $acquisition_date = '';
            $acquisition_age = '';
            $source = '';
            $cost = '';
            $notes = '';
        } else {
            $error = 'Failed to add flock.';
        }
    }
}

// Include header
include_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Flock</h1>
        <a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Flocks
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Flock Information</h6>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="breed" class="form-label">Breed <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="breed" name="breed" value="<?php echo isset($breed) ? $breed : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="batch_name" class="form-label">Batch Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="batch_name" name="batch_name" value="<?php echo isset($batch_name) ? $batch_name : ''; ?>" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo isset($quantity) ? $quantity : ''; ?>" min="1" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="acquisition_date" class="form-label">Acquisition Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="acquisition_date" name="acquisition_date" value="<?php echo isset($acquisition_date) ? $acquisition_date : date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="acquisition_age" class="form-label">Acquisition Age (Days)</label>
                            <input type="number" class="form-control" id="acquisition_age" name="acquisition_age" value="<?php echo isset($acquisition_age) ? $acquisition_age : '0'; ?>" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="source" class="form-label">Source</label>
                            <input type="text" class="form-control" id="source" name="source" value="<?php echo isset($source) ? $source : ''; ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cost" class="form-label">Cost</label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo get_setting('currency', '$'); ?></span>
                                <input type="number" class="form-control" id="cost" name="cost" value="<?php echo isset($cost) ? $cost : '0.00'; ?>" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($notes) ? $notes : ''; ?></textarea>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary">Add Flock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>

