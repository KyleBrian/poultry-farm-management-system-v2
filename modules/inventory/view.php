<?php
/**
 * File: modules/inventory/view.php
 * View inventory item details
 * @version 1.0.2
 * @integration_verification PMSFV-059
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check if item ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'Invalid inventory item ID.');
    header("Location: index.php");
    exit();
}

$item_id = intval($_GET['id']);

// Get inventory item details
$item = db_query_row($pdo, "
    SELECT i.*, u.username as created_by_name
    FROM inventory i
    JOIN users u ON i.created_by = u.id
    WHERE i.id = ?
", [$item_id]);

if (!$item) {
    set_flash_message('error', 'Inventory item not found.');
    header("Location: index.php");
    exit();
}

// Page title
$page_title = "View Inventory Item: " . $item['item_name'];

// Get usage history
$usage_history = db_query($pdo, "
    SELECT * FROM inventory_usage
    WHERE inventory_id = ?
    ORDER BY usage_date DESC
    LIMIT 10
", [$item_id]);

// Include header
include '../../includes/header.php';
?>

<!-- View Inventory Item -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($item['item_name']); ?></h1>
        <div>
            <?php if (has_permission('manage_inventory')): ?>
                <a href="edit.php?id=<?php echo $item_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mr-2">
                    <i class="fas fa-edit mr-2"></i> Edit Item
                </a>
            <?php endif; ?>
            <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Back to Inventory
            </a>
        </div>
    </div>
    
    <?php display_flash_message(); ?>
    
    <!-- Item Details -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Item Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Category</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($item['category']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Quantity</h3>
                <p class="mt-1 text-lg text-gray-900">
                    <?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?>
                    <?php if ($item['status'] == 'in_stock' && $item['quantity'] <= $item['reorder_point'] && $item['reorder_point'] > 0): ?>
                        <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            Low Stock
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Status</h3>
                <p class="mt-1">
                    <?php
                    $status_color = '';
                    switch ($item['status']) {
                        case 'in_stock':
                            $status_color = 'green';
                            break;
                        case 'depleted':
                            $status_color = 'gray';
                            break;
                        case 'expired':
                            $status_color = 'red';
                            break;
                    }
                    ?>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                        <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                    </span>
                </p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Unit Cost</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_currency($item['unit_cost']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Total Value</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_currency($item['quantity'] * $item['unit_cost']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Reorder Point</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo number_format($item['reorder_point'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Purchase Date</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_date($item['purchase_date']); ?></p>
            </div>
            <?php if (!empty($item['expiry_date'])): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Expiry Date</h3>
                    <?php
                    $days_until_expiry = (strtotime($item['expiry_date']) - time()) / (60 * 60 * 24);
                    $expiry_class = $days_until_expiry <= 7 ? 'text-red-600 font-bold' : ($days_until_expiry <= 30 ? 'text-yellow-600' : 'text-gray-900');
                    ?>
                    <p class="mt-1 text-lg <?php echo $expiry_class; ?>">
                        <?php echo format_date($item['expiry_date']); ?>
                        <?php if ($days_until_expiry > 0): ?>
                            (<?php echo floor($days_until_expiry); ?> days remaining)
                        <?php else: ?>
                            (Expired)
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
            <?php if (!empty($item['supplier'])): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Supplier</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($item['supplier']); ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($item['storage_location'])): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Storage Location</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($item['storage_location']); ?></p>
                </div>
            <?php endif; ?>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Created By</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($item['created_by_name']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Created At</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_datetime($item['created_at']); ?></p>
            </div>
        </div>
        
        <?php if (!empty($item['notes'])): ?>
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-500">Notes</h3>
                <div class="mt-1 p-4 bg-gray-50 rounded-md">
                    <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($item['notes'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Usage History -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Usage History</h2>
        
        <?php if (count($usage_history) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Used</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Used By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($usage_history as $usage): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo format_date($usage['usage_date']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo number_format($usage['quantity_used'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($usage['purpose']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($usage['used_by_name']); ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600">No usage history found for this item.</p>
        <?php endif; ?>
        
        <?php if (has_permission('manage_inventory') && $item['status'] == 'in_stock' && $item['quantity'] > 0): ?>
            <div class="mt-6">
                <a href="record_usage.php?id=<?php echo $item_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-clipboard-list mr-2"></i> Record Usage
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

