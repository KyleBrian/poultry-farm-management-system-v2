<?php
/**
 * File: modules/sales/add.php
 * Add new sale
 * @version 1.0.2
 * @integration_verification PMSFV-051
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check permission
if (!has_permission('manage_sales')) {
    set_flash_message('error', 'You do not have permission to add sales.');
    header("Location: index.php");
    exit();
}

// Page title
$page_title = "Add New Sale";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Validate input
        $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
        $customer_name = trim($_POST['customer_name']);
        $customer_contact = trim($_POST['customer_contact'] ?? '');
        $sale_date = $_POST['sale_date'];
        $payment_method = $_POST['payment_method'];
        $payment_status = $_POST['payment_status'];
        $notes = trim($_POST['notes'] ?? '');
        
        // Validate items
        $items = [];
        $total_amount = 0;
        
        for ($i = 0; $i < count($_POST['item_type']); $i++) {
            if (empty($_POST['item_type'][$i]) || empty($_POST['quantity'][$i]) || empty($_POST['unit_price'][$i])) {
                continue;
            }
            
            $item_type = $_POST['item_type'][$i];
            $description = $_POST['description'][$i];
            $quantity = floatval($_POST['quantity'][$i]);
            $unit_price = floatval($_POST['unit_price'][$i]);
            $item_total = $quantity * $unit_price;
            
            $items[] = [
                'item_type' => $item_type,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'item_total' => $item_total
            ];
            
            $total_amount += $item_total;
        }
        
        if (empty($items)) {
            throw new Exception("Please add at least one item to the sale.");
        }
        
        // Insert sale record
        $sale_data = [
            'customer_id' => $customer_id,
            'customer_name' => $customer_name,
            'customer_contact' => $customer_contact,
            'sale_date' => $sale_date,
            'total_amount' => $total_amount,
            'payment_method' => $payment_method,
            'payment_status' => $payment_status,
            'notes' => $notes,
            'created_by' => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $sale_id = db_insert($pdo, 'sales', $sale_data);
        
        if (!$sale_id) {
            throw new Exception("Failed to create sale record.");
        }
        
        // Insert sale items
        foreach ($items as $item) {
            $item_data = [
                'sale_id' => $sale_id,
                'item_type' => $item['item_type'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'item_total' => $item['item_total']
            ];
            
            $item_id = db_insert($pdo, 'sale_items', $item_data);
            
            if (!$item_id) {
                throw new Exception("Failed to add sale item.");
            }
            
            // Update inventory if item is from inventory
            if ($item['item_type'] == 'egg' || $item['item_type'] == 'bird' || $item['item_type'] == 'inventory') {
                // Logic to update inventory would go here
                // This would depend on how inventory is tracked in the system
            }
        }
        
        // Record financial transaction
        if ($payment_status == 'paid') {
            $transaction_data = [
                'transaction_date' => $sale_date,
                'transaction_type' => 'income',
                'amount' => $total_amount,
                'description' => "Sale #$sale_id: $customer_name",
                'payment_method' => $payment_method,
                'status' => 'completed',
                'created_by' => $_SESSION['user_id']
            ];
            
            db_insert($pdo, 'financial_transactions', $transaction_data);
        }
        
        // Log activity
        log_activity($pdo, $_SESSION['user_id'], 'add_sale', "Added new sale #$sale_id for $customer_name");
        
        // Commit transaction
        $pdo->commit();
        
        set_flash_message('success', "Sale has been recorded successfully.");
        header("Location: view.php?id=$sale_id");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Get customers for dropdown
$customers = db_query($pdo, "SELECT id, name, contact_person, phone FROM customers ORDER BY name");

// Include header
include '../../includes/header.php';
?>

<!-- Add New Sale -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Add New Sale</h1>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Back to Sales
        </a>
    </div>
    
    <?php display_flash_message(); ?>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="saleForm">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Customer Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">Select Customer (Optional)</label>
                    <select id="customer_id" name="customer_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" onchange="populateCustomerInfo()">
                        <option value="">-- New Customer --</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($customer['name']); ?>"
                                    data-contact="<?php echo htmlspecialchars($customer['contact_person'] . ' | ' . $customer['phone']); ?>">
                                <?php echo htmlspecialchars($customer['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Customer Name *</label>
                    <input type="text" id="customer_name" name="customer_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                <div class="md:col-span-2">
                    <label for="customer_contact" class="block text-sm font-medium text-gray-700 mb-1">Customer Contact Information</label>
                    <input type="text" id="customer_contact" name="customer_contact" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Sale Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="sale_date" class="block text-sm font-medium text-gray-700 mb-1">Sale Date *</label>
                    <input type="date" id="sale_date" name="sale_date" value="<?php echo date('Y-m-d'); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                    <select id="payment_method" name="payment_method" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>
                <div>
                    <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment Status *</label>
                    <select id="payment_status" name="payment_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Sale Items</h2>
                <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded" onclick="addItem()">
                    <i class="fas fa-plus mr-2"></i> Add Item
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="itemsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr class="item-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select name="item_type[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                                    <option value="egg">Eggs</option>
                                    <option value="bird">Birds</option>
                                    <option value="feed">Feed</option>
                                    <option value="medication">Medication</option>
                                    <option value="equipment">Equipment</option>
                                    <option value="other">Other</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="text" name="description[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="number" name="quantity[]" min="0.01" step="0.01" value="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-right item-quantity" required onchange="calculateItemTotal(this)">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="number" name="unit_price[]" min="0.01" step="0.01" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-right item-price" required onchange="calculateItemTotal(this)">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="item-total">0.00</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button type="button" class="text-red-500 hover:text-red-700" onclick="removeItem(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50">
                            <td colspan="4" class="px-6 py-4 text-right font-medium">Total:</td>
                            <td class="px-6 py-4 text-right font-bold" id="grandTotal">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Additional Information</h2>
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                <i class="fas fa-save mr-2"></i> Save Sale
            </button>
        </div>
    </form>
</div>

<script>
function populateCustomerInfo() {
    const customerSelect = document.getElementById('customer_id');
    const customerNameInput = document.getElementById('customer_name');
    const customerContactInput = document.getElementById('customer_contact');
    
    if (customerSelect.value) {
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        customerNameInput.value = selectedOption.getAttribute('data-name');
        customerContactInput.value = selectedOption.getAttribute('data-contact');
    } else {
        customerNameInput.value = '';
        customerContactInput.value = '';
    }
}

function calculateItemTotal(input) {
    const row = input.closest('tr');
    const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const total = quantity * price;
    
    row.querySelector('.item-total').textContent = total.toFixed(2);
    
    calculateGrandTotal();
}

function calculateGrandTotal() {
    const totals = document.querySelectorAll('.item-total');
    let grandTotal = 0;
    
    totals.forEach(total => {
        grandTotal += parseFloat(total.textContent) || 0;
    });
    
    document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
}

function addItem() {
    const tbody = document.getElementById('itemsBody');
    const template = document.querySelector('.item-row').cloneNode(true);
    
    // Reset values
    template.querySelector('input[name="description[]"]').value = '';
    template.querySelector('input[name="quantity[]"]').value = '1';
    template.querySelector('input[name="unit_price[]"]').value = '';
    template.querySelector('.item-total').textContent = '0.00';
    
    tbody.appendChild(template);
}

function removeItem(button) {
    const tbody = document.getElementById('itemsBody');
    
    // Don't remove if it's the only row
    if (tbody.children.length > 1) {
        const row = button.closest('tr');
        row.remove();
        calculateGrandTotal();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    populateCustomerInfo();
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

