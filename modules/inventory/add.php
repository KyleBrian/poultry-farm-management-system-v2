<?php
/**
 * File: modules/inventory/add.php
 * Add new inventory item
 * @version 1.0.2
 * @integration_verification PMSFV-058
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check permission
if (!has_permission('manage_inventory')) {
    set_flash_message('error', 'You do not have permission to add inventory items.');
    header("Location: index.php");
    exit();
}

// Page title
$page_title = "Add Inventory Item";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $item_name = trim($_POST['item_name']);
        $category = trim($_POST['category']);
        $quantity = floatval($_POST['quantity']);
        $unit_of_measure = trim($_POST['unit_of_measure']);
        $unit_cost = floatval($_POST['unit_cost']);
        $supplier = trim($_POST['supplier'] ?? '');
        $purchase_date = $_POST['purchase_date'];
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $reorder_point = intval($_POST['reorder_point'] ?? 0);
        $storage_location = trim($_POST['storage_location'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($item_name) || empty($category) || empty($unit_of_measure)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }
        
        if ($unit_cost < 0) {
            throw new Exception("Unit cost cannot be negative.");
        }
        
        // Insert inventory item
        $data = [
            'item_name' => $item_name,
            'category' => $category,
            'quantity' => $quantity,
            'unit_of_measure' => $unit_of_measure,
            'unit_cost' => $unit_cost,
            'supplier' => $supplier,
            'purchase_date' => $purchase_date,
            'expiry_date' => $expiry_date,
            'reorder_point' => $reorder_point,
            'storage_location' => $storage_location,
            'notes' => $notes,
            'status' => 'in_stock',
            'created_by' => $_SESSION['user_id']
        ];
        
        $item_id = db_insert($pdo, 'inventory', $data);
        
        if ($item_id) {
            // Log activity
            log_activity($pdo, $_SESSION['user_id'], 'add_inventory', "Added inventory item: {$item_name}");
            
            // Record financial transaction if cost is provided
            if ($unit_cost > 0) {
                $total_cost = $unit_cost * $quantity;
                $transaction_data = [
                    'transaction_date' => $purchase_date,
                    'transaction_type' => 'expense',
                    'amount' => $total_cost,
                    'description' => "Purchase of {$quantity} {$unit_of_measure} of {$item_name}",
                    'payment_method' => 'cash', // Default payment method
                    'status' => 'completed',
                    'created_by' => $_SESSION['user_id']
                ];
                
                db_insert($pdo, 'financial_transactions', $transaction_data);
            }
            
            set_flash_message('success', "Inventory item '{$item_name}' added successfully.");
            header("Location: index.php");
            exit();
        } else {
            throw new Exception("Failed to add inventory item.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get categories for dropdown
$categories = db_query($pdo, "SELECT DISTINCT category FROM inventory ORDER BY category");

// Include header
include '../../includes/header.php';
?>

<!-- Add Inventory Item -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Add Inventory Item</h1>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Back to Inventory
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
                <!-- Item Name -->
                <div>
                    <label for="item_name" class="block text-sm font-medium text-gray-700 mb-1">Item Name *</label>
                    <input type="text" id="item_name" name="item_name" value="<?php echo isset($_POST['item_name']) ? htmlspecialchars($_POST['item_name']) : ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select id="category" name="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                        <option value="">Select Category</option>
                        <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo (isset($_POST['category']) && $_POST['category'] == $cat['category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <option value="Feed">Feed</option>
                        <option value="Medication">Medication</option>
                        <option value="Equipment">Equipment</option>
                        <option value="Supplies">Supplies</option>
                        <option value="Packaging">Packaging</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" step="0.01" min="0.01" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Unit of Measure -->
                <div>
                    <label for="unit_of_measure" class="block text-sm font-medium text-gray-700 mb-1">Unit of Measure *</label>
                    <select id="unit_of_measure" name="unit_of_measure" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                        <option value="">Select Unit</option>
                        <option value="kg" <?php echo (isset($_POST['unit_of_measure']) && $_POST['unit_of_measure'] == 'kg') ? 'selected' : ''; ?>>Kilogram (kg)</option>
                        <option value="g" <?php echo (isset($_POST['unit_of_measure']) && $_POST['unit_of_measure'] == 'g') ? 'selected' : ''; ?>>Gram (g)</option>
                        <option value="l" <?php echo (isset($_POST['unit_of_measure']) && $_POST['unit_of_measure'] == 'l') ? 'selected' : ''; ?>>Liter (l)</option>
                        <option value="ml" <?php echo (isset($_POST['unit_of_measure']) && $_POST['unit_of_measure'] == 'ml') ? 'selected' : ''; ?>>Milliliter (ml)</option>
                        <option value="pcs" <?php echo (isset($_POST['unit_of_measure']) && $_POST['unit_of_measure'] == 'pcs') ? 'selected' : ''; ?>>Pieces (pcs)</option>
                        <option value="box" <?php echo (isset($_POST['unit_of_measure']) && $_POST['unit_of_measure'] == 'box') ? 'selected' : ''; ?>>Box</option>
                        <option value="bottle" <?php echo (isset($_POST['unit_of_measure']) && $_POST['unit_of_measure'] == 'bottle') ? 'selected' : ''; ?>>Bottle</option>
                        <option value="packet" <?php echo (isset($_POST['unit_of_measure']) && $_POST['unit_of_measure'] == 'packet') ? 'selected' : ''; ?>>Packet</option>
                    </select>
                </div>
                
                <!-- Unit Cost -->
                <div>
                    <label for="unit_cost" class="block text-sm font-medium text-gray-700 mb-1">Unit Cost</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" value="<?php echo isset($_POST['unit_cost']) ? $_POST['unit_cost'] : '0.00'; ?>" class="w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </div>
                </div>
                
                <!-- Supplier -->
                <div>
                    <label for="supplier" class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <input type="text" id="supplier" name="supplier" value="<?php echo isset($_POST['supplier']) ? htmlspecialchars($_POST['supplier']) : ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                
                <!-- Purchase Date -->
                <div>
                    <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-1">Purchase Date *</label>
                    <input type="date" id="purchase_date" name="purchase_date" value="<?php echo isset($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d'); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Expiry Date -->
                <div>
                    <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="<?php echo isset($_POST['expiry_date']) ? $_POST['expiry_date'] : ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                
                <!-- Reorder Point -->
                <div>
                    <label for="reorder_point" class="block text-sm font-medium text-gray-700 mb-1">Reorder Point</label>
                    <input type="number" id="reorder_point" name="reorder_point" min="0" value="<?php echo isset($_POST['reorder_point']) ? $_POST['reorder_point'] : '0'; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                
                <!-- Storage Location -->
                <div>
                    <label for="storage_location" class="block text-sm font-medium text-gray-700 mb-1">Storage Location</label>
                    <input type="text" id="storage_location" name="storage_location" value="<?php echo isset($_POST['storage_location']) ? htmlspecialchars($_POST['storage_location']) : ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                
                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-save mr-2"></i> Add Item
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

