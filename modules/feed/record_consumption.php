<?php
/**
 * File: modules/feed/record_consumption.php
 * Record feed consumption
 * @version 1.0.2
 * @integration_verification PMSFV-018
 */
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check permission
if (!has_permission('manage_feed') && !has_permission('record_feed')) {
    set_flash_message('error', 'You do not have permission to record feed consumption.');
    header("Location: index.php");
    exit();
}

// Page title
$page_title = "Record Feed Consumption";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $flock_id = $_POST['flock_id'];
        $feed_type = $_POST['feed_type'];
        $quantity = floatval($_POST['quantity']);
        $feeding_date = $_POST['feeding_date'];
        $notes = $_POST['notes'] ?? '';
        
        if (empty($flock_id) || empty($feed_type) || empty($feeding_date)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }
        
        // Check if there's enough feed in inventory
        $available_feed = db_query_value($pdo, "
            SELECT SUM(quantity) 
            FROM feed_inventory 
            WHERE feed_type = ? AND status = 'in_stock'
        ", [$feed_type]) ?? 0;
        
        if ($quantity > $available_feed) {
            throw new Exception("Not enough feed in inventory. Available: " . number_format($available_feed, 2) . " kg");
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Insert feed consumption record
        $stmt = $pdo->prepare("
            INSERT INTO feed_consumption (
                flock_id, feed_type, quantity, feeding_date, 
                notes, recorded_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $flock_id,
            $feed_type,
            $quantity,
            $feeding_date,
            $notes,
            $_SESSION['user_id']
        ]);
        
        // Update feed inventory (FIFO method)
        $remaining_quantity = $quantity;
        
        // Get feed inventory items sorted by expiry date (FIFO)
        $inventory_items = db_query($pdo, "
            SELECT id, quantity 
            FROM feed_inventory 
            WHERE feed_type = ? AND status = 'in_stock' 
            ORDER BY expiry_date ASC, purchase_date ASC
        ", [$feed_type]);
        
        foreach ($inventory_items as $item) {
            if ($remaining_quantity <= 0) {
                break;
            }
            
            $deduct_amount = min($remaining_quantity, $item['quantity']);
            $new_quantity = $item['quantity'] - $deduct_amount;
            
            // Update inventory item
            $stmt = $pdo->prepare("
                UPDATE feed_inventory 
                SET quantity = ?, 
                    status = CASE WHEN ? <= 0 THEN 'depleted' ELSE status END,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$new_quantity, $new_quantity, $item['id']]);
            
            $remaining_quantity -= $deduct_amount;
        }
        
        // Log activity
        log_activity($pdo, $_SESSION['user_id'], 'record_feed_consumption', "Recorded {$quantity} kg of {$feed_type} feed consumption for flock #{$flock_id}");
        
        // Commit transaction
        $pdo->commit();
        
        set_flash_message('success', "Feed consumption recorded successfully.");
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
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

// Get available feed types
$feed_types = db_query($pdo, "
    SELECT DISTINCT feed_type 
    FROM feed_inventory 
    WHERE status = 'in_stock' AND quantity > 0
    ORDER BY feed_type
");

// Include header
include '../../includes/header.php';
?>

<!-- Record Feed Consumption -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Record Feed Consumption</h1>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Back to Feed Management
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
                
                <!-- Feed Type -->
                <div>
                    <label for="feed_type" class="block text-sm font-medium text-gray-700 mb-1">Feed Type *</label>
                    <select id="feed_type" name="feed_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required onchange="checkAvailableQuantity()">
                        <option value="">Select Feed Type</option>
                        <?php foreach ($feed_types as $type): ?>
                            <option value="<?php echo $type['feed_type']; ?>" <?php echo (isset($_POST['feed_type']) && $_POST['feed_type'] == $type['feed_type']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['feed_type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="available_quantity" class="mt-1 text-sm text-gray-500"></p>
                </div>
                
                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity (kg) *</label>
                    <input type="number" id="quantity" name="quantity" step="0.01" min="0.01" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Feeding Date -->
                <div>
                    <label for="feeding_date" class="block text-sm font-medium text-gray-700 mb-1">Feeding Date *</label>
                    <input type="date" id="feeding_date" name="feeding_date" value="<?php echo isset($_POST['feeding_date']) ? $_POST['feeding_date'] : date('Y-m-d'); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo isset($_POST['notes']) ? $_POST['notes'] : ''; ?></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-save mr-2"></i> Record Consumption
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function checkAvailableQuantity() {
    const feedType = document.getElementById('feed_type').value;
    const availableQuantityElement = document.getElementById('available_quantity');
    
    if (!feedType) {
        availableQuantityElement.textContent = '';
        return;
    }
    
    // Fetch available quantity via AJAX
    fetch('get_available_feed.php?feed_type=' + encodeURIComponent(feedType))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                availableQuantityElement.textContent = 'Available: ' + data.quantity + ' kg';
                availableQuantityElement.className = 'mt-1 text-sm text-green-600';
            } else {
                availableQuantityElement.textContent = 'Error: ' + data.message;
                availableQuantityElement.className = 'mt-1 text-sm text-red-600';
            }
        })
        .catch(error => {
            availableQuantityElement.textContent = 'Error fetching available quantity';
            availableQuantityElement.className = 'mt-1 text-sm text-red-600';
        });
}

// Check available quantity on page load if feed type is selected
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('feed_type').value) {
        checkAvailableQuantity();
    }
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

