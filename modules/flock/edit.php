<?php
/**
 * File: modules/flock/edit.php
 * Edit flock details
 * @version 1.0.2
 * @integration_verification PMSFV-023
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check permission
if (!has_permission('manage_flock')) {
    set_flash_message('error', 'You do not have permission to edit flocks.');
    header("Location: index.php");
    exit();
}

// Check if flock ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'Invalid flock ID.');
    header("Location: index.php");
    exit();
}

$flock_id = intval($_GET['id']);

// Get flock details
$flock = db_query_row($pdo, "SELECT * FROM flocks WHERE id = ?", [$flock_id]);

if (!$flock) {
    set_flash_message('error', 'Flock not found.');
    header("Location: index.php");
    exit();
}

// Page title
$page_title = "Edit Flock: " . $flock['name'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $name = trim($_POST['name']);
        $breed = trim($_POST['breed']);
        $purpose = trim($_POST['purpose']);
        $acquisition_date = $_POST['acquisition_date'];
        $initial_count = intval($_POST['initial_count']);
        $mortality = intval($_POST['mortality']);
        $status = $_POST['status'];
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($name) || empty($breed) || empty($purpose) || empty($acquisition_date)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        if ($initial_count <= 0) {
            throw new Exception("Initial count must be greater than zero.");
        }
        
        if ($mortality < 0 || $mortality > $initial_count) {
            throw new Exception("Mortality cannot be negative or greater than initial count.");
        }
        
        // Update flock
        $data = [
            'name' => $name,
            'breed' => $breed,
            'purpose' => $purpose,
            'acquisition_date' => $acquisition_date,
            'initial_count' => $initial_count,
            'mortality' => $mortality,
            'status' => $status,
            'notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = db_update($pdo, 'flocks', $data, 'id = ?', [$flock_id]);
        
        if ($result !== false) {
            // Log activity
            log_activity($pdo, $_SESSION['user_id'], 'edit_flock', "Updated flock: $name");
            
            set_flash_message('success', "Flock '$name' updated successfully.");
            header("Location: view.php?id=$flock_id");
            exit();
        } else {
            throw new Exception("Failed to update flock.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Include header
include '../../includes/header.php';
?>

<!-- Edit Flock -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit Flock: <?php echo htmlspecialchars($flock['name']); ?></h1>
        <div>
            <a href="view.php?id=<?php echo $flock_id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Back to Flock Details
            </a>
        </div>
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
                <!-- Flock Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Flock Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($flock['name']); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Breed -->
                <div>
                    <label for="breed" class="block text-sm font-medium text-gray-700 mb-1">Breed *</label>
                    <input type="text" id="breed" name="breed" value="<?php echo htmlspecialchars($flock['breed']); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Purpose -->
                <div>
                    <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1">Purpose *</label>
                    <select id="purpose" name="purpose" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                        <option value="layers" <?php echo ($flock['purpose'] == 'layers') ? 'selected' : ''; ?>>Layers</option>
                        <option value="broilers" <?php echo ($flock['purpose'] == 'broilers') ? 'selected' : ''; ?>>Broilers</option>
                        <option value="breeding" <?php echo ($flock['purpose'] == 'breeding') ? 'selected' : ''; ?>>Breeding</option>
                        <option value="dual_purpose" <?php echo ($flock['purpose'] == 'dual_purpose') ? 'selected' : ''; ?>>Dual Purpose</option>
                    </select>
                </div>
                
                <!-- Acquisition Date -->
                <div>
                    <label for="acquisition_date" class="block text-sm font-medium text-gray-700 mb-1">Acquisition Date *</label>
                    <input type="date" id="acquisition_date" name="acquisition_date" value="<?php echo $flock['acquisition_date']; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Initial Count -->
                <div>
                    <label for="initial_count" class="block text-sm font-medium text-gray-700 mb-1">Initial Count *</label>
                    <input type="number" id="initial_count" name="initial_count" value="<?php echo $flock['initial_count']; ?>" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Mortality -->
                <div>
                    <label for="mortality" class="block text-sm font-medium text-gray-700 mb-1">Mortality</label>
                    <input type="number" id="mortality" name="mortality" value="<?php echo $flock['mortality']; ?>" min="0" max="<?php echo $flock['initial_count']; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                        <option value="active" <?php echo ($flock['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="sold" <?php echo ($flock['status'] == 'sold') ? 'selected' : ''; ?>>Sold</option>
                        <option value="culled" <?php echo ($flock['status'] == 'culled') ? 'selected' : ''; ?>>Culled</option>
                        <option value="deceased" <?php echo ($flock['status'] == 'deceased') ? 'selected' : ''; ?>>Deceased</option>
                    </select>
                </div>
                
                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo htmlspecialchars($flock['notes']); ?></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-save mr-2"></i> Update Flock
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

