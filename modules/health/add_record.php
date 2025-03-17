<?php
/**
 * File: modules/health/add_record.php
 * Add new health record
 * @version 1.0.1
 * @integration_verification PMSFV-030
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check permission
if (!has_permission('manage_health') && !has_permission('record_health')) {
    set_flash_message('error', 'You do not have permission to add health records.');
    header("Location: index.php");
    exit();
}

// Page title
$page_title = "Add Health Record";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $flock_id = $_POST['flock_id'];
        $record_date = $_POST['record_date'];
        $record_type = $_POST['record_type'];
        $symptoms = $_POST['symptoms'] ?? '';
        $diagnosis = $_POST['diagnosis'] ?? '';
        $treatment = $_POST['treatment'] ?? '';
        $medication = $_POST['medication'] ?? '';
        $recovery_status = $_POST['recovery_status'] ?? 'ongoing';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($flock_id) || empty($record_date) || empty($record_type)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        // Insert health record
        $data = [
            'flock_id' => $flock_id,
            'record_date' => $record_date,
            'record_type' => $record_type,
            'symptoms' => $symptoms,
            'diagnosis' => $diagnosis,
            'treatment' => $treatment,
            'medication' => $medication,
            'recovery_status' => $recovery_status,
            'notes' => $notes,
            'recorded_by' => $_SESSION['user_id']
        ];
        
        $record_id = db_insert($pdo, 'health_records', $data);
        
        if ($record_id) {
            // Log activity
            log_activity($pdo, $_SESSION['user_id'], 'add_health_record', "Added health record for flock #{$flock_id}");
            
            // Update flock health status if it's an illness
            if ($record_type == 'illness') {
                db_update($pdo, 'flocks', ['health_status' => 'sick'], 'id = ?', [$flock_id]);
            }
            
            set_flash_message('success', "Health record added successfully.");
            header("Location: index.php");
            exit();
        } else {
            throw new Exception("Failed to add health record.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get active flocks
$flocks = db_query($pdo, "
    SELECT id, name, breed, current_count 
    FROM flocks 
    WHERE status = 'active' 
    ORDER BY name
");

// Include header
include '../../includes/header.php';
?>

<!-- Add Health Record -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Add Health Record</h1>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Back to Health Records
        </a>
    </div>
    
    <?php display_flash_message(); ?>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Flock Selection -->
                <div>
                    <label for="flock_id" class="block text-sm font-medium text-gray-700 mb-1">Flock *</label>
                    <select id="flock_id" name="flock_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                        <option value="">Select Flock</option>
                        <?php foreach ($flocks as $flock): ?>
                            <option value="<?php echo $flock['id']; ?>" <?php echo (isset($_POST['flock_id']) && $_POST['flock_id'] == $flock['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($flock['name'] . ' (' . $flock['breed'] . ', ' . $flock['current_count'] . ' birds)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Record Date -->
                <div>
                    <label for="record_date" class="block text-sm font-medium text-gray-700 mb-1">Record Date *</label>
                    <input type="date" id="record_date" name="record_date" value="<?php echo isset($_POST['record_date']) ? $_POST['record_date'] : date('Y-m-d'); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Record Type -->
                <div>
                    <label for="record_type" class="block text-sm font-medium text-gray-700 mb-1">Record Type *</label>
                    <select id="record_type" name="record_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required onchange="toggleFields()">
                        <option value="">Select Record Type</option>
                        <option value="routine_check" <?php echo (isset($_POST['record_type']) && $_POST['record_type'] == 'routine_check') ? 'selected' : ''; ?>>Routine Check</option>
                        <option value="illness" <?php echo (isset($_POST['record_type']) && $_POST['record_type'] == 'illness') ? 'selected' : ''; ?>>Illness</option>
                        <option value="treatment" <?php echo (isset($_POST['record_type']) && $_POST['record_type'] == 'treatment') ? 'selected' : ''; ?>>Treatment</option>
                        <option value="recovery" <?php echo (isset($_POST['record_type']) && $_POST['record_type'] == 'recovery') ? 'selected' : ''; ?>>Recovery</option>
                        <option value="mortality" <?php echo (isset($_POST['record_type']) && $_POST['record_type'] == 'mortality') ? 'selected' : ''; ?>>Mortality</option>
                    </select>
                </div>
                
                <!-- Recovery Status -->
                <div id="recovery_status_field" style="display: none;">
                    <label for="recovery_status" class="block text-sm font-medium text-gray-700 mb-1">Recovery Status</label>
                    <select id="recovery_status" name="recovery_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="ongoing" <?php echo (isset($_POST['recovery_status']) && $_POST['recovery_status'] == 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="recovered" <?php echo (isset($_POST['recovery_status']) && $_POST['recovery_status'] == 'recovered') ? 'selected' : ''; ?>>Recovered</option>
                        <option value="deceased" <?php echo (isset($_POST['recovery_status']) && $_POST['recovery_status'] == 'deceased') ? 'selected' : ''; ?>>Deceased</option>
                    </select>
                </div>
                
                <!-- Symptoms -->
                <div id="symptoms_field" class="md:col-span-2" style="display: none;">
                    <label for="symptoms" class="block text-sm font-medium text-gray-700 mb-1">Symptoms</label>
                    <textarea id="symptoms" name="symptoms" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo isset($_POST['symptoms']) ? htmlspecialchars($_POST['symptoms']) : ''; ?></textarea>
                </div>
                
                <!-- Diagnosis -->
                <div id="diagnosis_field" class="md:col-span-2" style="display: none;">
                    <label for="diagnosis" class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                    <textarea id="diagnosis" name="diagnosis" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo isset($_POST['diagnosis']) ? htmlspecialchars($_POST['diagnosis']) : ''; ?></textarea>
                </div>
                
                <!-- Treatment -->
                <div id="treatment_field" class="md:col-span-2" style="display: none;">
                    <label for="treatment" class="block text-sm font-medium text-gray-700 mb-1">Treatment</label>
                    <textarea id="treatment" name="treatment" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo isset($_POST['treatment']) ? htmlspecialchars($_POST['treatment']) : ''; ?></textarea>
                </div>
                
                <!-- Medication -->
                <div id="medication_field" class="md:col-span-2" style="display: none;">
                    <label for="medication" class="block text-sm font-medium text-gray-700 mb-1">Medication</label>
                    <textarea id="medication" name="medication" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo isset($_POST['medication']) ? htmlspecialchars($_POST['medication']) : ''; ?></textarea>
                </div>
                
                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-save mr-2"></i> Add Record
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleFields() {
    const recordType = document.getElementById('record_type').value;
    
    // Hide all conditional fields first
    document.getElementById('symptoms_field').style.display = 'none';
    document.getElementById('diagnosis_field').style.display = 'none';
    document.getElementById('treatment_field').style.display = 'none';
    document.getElementById('medication_field').style.display = 'none';
    document.getElementById('recovery_status_field').style.display = 'none';
    
    // Show relevant fields based on record type
    if (recordType === 'illness') {
        document.getElementById('symptoms_field').style.display = 'block';
        document.getElementById('diagnosis_field').style.display = 'block';
        document.getElementById('recovery_status_field').style.display = 'block';
    } else if (recordType === 'treatment') {
        document.getElementById('treatment_field').style.display = 'block';
        document.getElementById('medication_field').style.display = 'block';
        document.getElementById('recovery_status_field').style.display = 'block';
    } else if (recordType === 'recovery') {
        document.getElementById('recovery_status_field').style.display = 'block';
    } else if (recordType === 'mortality') {
        document.getElementById('symptoms_field').style.display = 'block';
        document.getElementById('diagnosis_field').style.display = 'block';
        document.getElementById('recovery_status_field').style.display = 'block';
        document.getElementById('recovery_status').value = 'deceased';
    }
}

// Initialize fields on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleFields();
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

