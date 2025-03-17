<?php
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Get all active flocks
$flocks_query = "SELECT id, name, batch_number, bird_count FROM flocks WHERE status = 'Active' ORDER BY name";
$flocks_result = $conn->query($flocks_query);
$flocks = [];
while ($row = $flocks_result->fetch_assoc()) {
    $flocks[] = $row;
}

// Get all vaccine types
$vaccines_query = "SELECT id, name, description, recommended_age FROM vaccine_types ORDER BY name";
$vaccines_result = $conn->query($vaccines_query);
$vaccines = [];
while ($row = $vaccines_result->fetch_assoc()) {
    $vaccines[] = $row;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $flock_id = intval($_POST['flock_id']);
    $vaccine_type_id = intval($_POST['vaccine_type_id']);
    $vaccination_date = mysqli_real_escape_string($conn, $_POST['vaccination_date']);
    $dosage = mysqli_real_escape_string($conn, $_POST['dosage']);
    $administration_method = mysqli_real_escape_string($conn, $_POST['administration_method']);
    $administered_by = mysqli_real_escape_string($conn, $_POST['administered_by']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Insert vaccination record
    $insert_query = "INSERT INTO vaccinations (flock_id, vaccine_type_id, vaccination_date, dosage, administration_method, administered_by, notes, status, created_by, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iisssssis", $flock_id, $vaccine_type_id, $vaccination_date, $dosage, $administration_method, $administered_by, $notes, $status, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $vaccination_id = $conn->insert_id;
        
        // Log activity
        $log_query = "INSERT INTO activity_logs (user_id, action, module, description, created_at) VALUES (?, 'create', 'vaccination', ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $description = "Added new vaccination record ID: $vaccination_id";
        $log_stmt->bind_param("is", $_SESSION['user_id'], $description);
        $log_stmt->execute();
        
        $_SESSION['success'] = "Vaccination record added successfully";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to add vaccination record: " . $conn->error;
    }
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Record Vaccination</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Vaccinations</a></li>
                        <li class="breadcrumb-item active">Record Vaccination</li>
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
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Vaccination Information</h3>
                        </div>
                        <form method="post" action="">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="flock_id">Flock</label>
                                            <select class="form-control" id="flock_id" name="flock_id" required>
                                                <option value="">Select Flock</option>
                                                <?php foreach ($flocks as $flock): ?>
                                                    <option value="<?php echo $flock['id']; ?>">
                                                        <?php echo htmlspecialchars($flock['name'] . ' (' . $flock['batch_number'] . ') - ' . $flock['bird_count'] . ' birds'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="vaccine_type_id">Vaccine</label>
                                            <select class="form-control" id="vaccine_type_id" name="vaccine_type_id" required>
                                                <option value="">Select Vaccine</option>
                                                <?php foreach ($vaccines as $vaccine): ?>
                                                    <option value="<?php echo $vaccine['id']; ?>" data-age="<?php echo $vaccine['recommended_age']; ?>">
                                                        <?php echo htmlspecialchars($vaccine['name'] . ' (' . $vaccine['recommended_age'] . ' days)'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="vaccination_date">Vaccination Date</label>
                                            <input type="date" class="form-control" id="vaccination_date" name="vaccination_date" required value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value="Scheduled">Scheduled</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dosage">Dosage</label>
                                            <input type="text" class="form-control" id="dosage" name="dosage" required placeholder="e.g., 0.5ml per bird">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="administration_method">Administration Method</label>
                                            <select class="form-control" id="administration_method" name="administration_method" required>
                                                <option value="Drinking Water">Drinking Water</option>
                                                <option value="Eye Drop">Eye Drop</option>
                                                <option value="Injection">Injection</option>
                                                <option value="Spray">Spray</option>
                                                <option value="Wing Web">Wing Web</option>
                                                <option value="Oral">Oral</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="administered_by">Administered By</label>
                                    <input type="text" class="form-control" id="administered_by" name="administered_by" placeholder="Name of person administering the vaccine">
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes or observations"></textarea>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save Record</button>
                                <a href="index.php" class="btn btn-default">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const flockSelect = document.getElementById('flock_id');
    const vaccineSelect = document.getElementById('vaccine_type_id');
    
    // Add event listener to vaccine select
    vaccineSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const recommendedAge = selectedOption.getAttribute('data-age');
        
        if (recommendedAge) {
            // You could use this to show a recommendation or warning
            console.log(`Recommended age for this vaccine: ${recommendedAge} days`);
        }
    });
});
</script>

<?php
require_once '../../includes/footer.php';
?>

