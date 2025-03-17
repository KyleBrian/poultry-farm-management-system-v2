<?php
/**
 * File: modules/egg_production/add.php
 * Record egg production
 * @version 1.0.1
 * @integration_verification PMSFV-038
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check permission
if (!has_permission('manage_eggs') && !has_permission('record_eggs')) {
    set_flash_message('error', 'You do not have permission to record egg production.');
    header("Location: index.php");
    exit();
}

// Page title
$page_title = "Record Egg Production";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $flock_id = $_POST['flock_id'];
        $collection_date = $_POST['collection_date'];
        $quantity = intval($_POST['quantity']);
        $broken_eggs = intval($_POST['broken_eggs'] ?? 0);
        $dirty_eggs = intval($_POST['dirty_eggs'] ?? 0);
        $notes = $_POST['notes'] ?? '';
        
        if (empty($flock_id) || empty($collection_date)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }
        
        // Check if record already exists for this flock and date
        $existing = db_query_row($pdo, "
            SELECT id FROM egg_production 
            WHERE flock_id = ? AND collection_date = ?
        ", [$flock_id, $collection_date]);
        
        if ($existing) {
            throw new Exception("Egg production record already exists for this flock and date.");
        }
        
        // Insert egg production record
        $data = [
            'flock_id' => $flock_id,
            'collection_date' => $collection_date,
            'quantity' => $quantity,
            'broken_eggs' => $broken_eggs,
            'dirty_eggs' => $dirty_eggs,
            'notes' => $notes,
            'recorded_by' => $_SESSION['user_id']
        ];
        
        $result = db_insert($pdo, 'egg_production', $data);
        
        if ($result) {
            // Log activity
            log_activity($pdo, $_SESSION['user_id'], 'record_egg_production', "Recorded {$quantity} eggs for flock #{$flock_id}");
            
            set_flash_message('success', "Egg production recorded successfully.");
            header("Location: index.php");
            exit();
        } else {
            throw new Exception("Failed to record egg production.");
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

<!-- Record Egg Production -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Record Egg Production</h1>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Back to Egg Production
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
                
                <!-- Collection Date -->
                <div>
                    <label for="collection_date" class="block text-sm font-medium text-gray-700 mb-1">Collection Date *</label>
                    <input type="date" id="collection_date" name="collection_date" value="<?php echo isset($_POST['collection_date']) ? $_POST['collection_date'] : date('Y-m-d'); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Total Eggs Collected *</label>
                    <input type="number" id="quantity" name="quantity" min="1" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Broken Eggs -->
                <div>
                    <label for="broken_eggs" class="block text-sm font-medium text-gray-700 mb-1">Broken Eggs</label>
                    <input type="number" id="broken_eggs" name="broken_eggs" min="0" value="<?php echo isset($_POST['broken_eggs']) ? $_POST['broken_eggs'] : '0'; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                
                <!-- Dirty Eggs -->
                <div>
                    <label for="dirty_eggs" class="block text-sm font-medium text-gray-700 mb-1">Dirty Eggs</label>
                    <input type="number" id="dirty_eggs" name="dirty_eggs" min="0" value="<?php echo isset($_POST['dirty_eggs']) ? $_POST['dirty_eggs'] : '0'; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                
                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo isset($_POST['notes']) ? $_POST['notes'] : ''; ?></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-save mr-2"></i> Record Production
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Calculate good eggs automatically
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    const brokenEggsInput = document.getElementById('broken_eggs');
    const dirtyEggsInput = document.getElementById('dirty_eggs');
    
    function updateGoodEggs() {
        const total = parseInt(quantityInput.value) || 0;
        const broken = parseInt(brokenEggsInput.value) || 0;
        const dirty = parseInt(dirtyEggsInput.value) || 0;
        
        // Ensure broken and dirty eggs don't exceed total
        if (broken + dirty > total) {
            alert('Broken and dirty eggs cannot exceed total eggs collected.');
            brokenEggsInput.value = 0;
            dirtyEggsInput.value = 0;
        }
    }
    
    quantityInput.addEventListener('change', updateGoodEggs);
    brokenEggsInput.addEventListener('change', updateGoodEggs);
    dirtyEggsInput.addEventListener('change', updateGoodEggs);
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

