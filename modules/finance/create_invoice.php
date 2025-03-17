<?php
/**
 * File: modules/finance/create_invoice.php
 * Create new invoice
 * @version 1.0.1
 * @integration_verification PMSFV-023
 */
$page_title = "Create Invoice";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Check if user has appropriate permissions
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    $_SESSION['error_msg'] = "You don't have permission to access this page.";
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Generate invoice number
        $year = date('Y');
        $month = date('m');
        $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(invoice_number, 10) AS UNSIGNED)) as max_id FROM invoices WHERE invoice_number LIKE 'INV-$year$month%'");
        $result = $stmt->fetch();
        $next_id = ($result['max_id'] ?? 0) + 1;
        $invoice_number = "INV-$year$month" . str_pad($next_id, 4, '0', STR_PAD_LEFT);
        
        // Insert invoice
        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                invoice_number, customer_id, customer_name, customer_contact,
                invoice_date, due_date, total_amount, paid_amount,
                status, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $invoice_number,
            $_POST['customer_id'] ?: null,
            $_POST['customer_name'],
            $_POST['customer_contact'],
            $_POST['invoice_date'],
            $_POST['due_date'],
            0, // Will be updated after adding items
            0, // No payment yet
            'draft',
            $_POST['notes'],
            $_SESSION['user_id']
        ]);
        
        $invoice_id = $pdo->lastInsertId();
        
        // Process invoice items
        $total_amount = 0;
        
        for ($i = 0; $i < count($_POST['item_description']); $i++) {
            if (empty($_POST['item_description'][$i])) continue;
            
            $item_type = $_POST['item_type'][$i];
            $description = $_POST['item_description'][$i];
            $quantity = floatval($_POST['item_quantity'][$i]);
            $unit_price = floatval($_POST['item_price'][$i]);
            $total_price = $quantity * $unit_price;
            
            $stmt = $pdo->prepare("
                INSERT INTO invoice_items (
                    invoice_id, item_type, description,
                    quantity, unit_price, total_price
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $invoice_id,
                $item_type,
                $description,
                $quantity,
                $unit_price,
                $total_price
            ]);
            
            $total_amount += $total_price;
        }
        
        // Update invoice total
        $stmt = $pdo->prepare("UPDATE invoices SET total_amount = ? WHERE id = ?");
        $stmt->execute([$total_amount, $invoice_id]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_msg'] = "Invoice created successfully.";
        header("Location: view_invoice.php?id=$invoice_id");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Error creating invoice: " . $e->getMessage();
    }
}

// Get customers for dropdown
$stmt = $pdo->query("SELECT id, name, contact_person, phone, email FROM customers ORDER BY name");
$customers = $stmt->fetchAll();
?>

<div class="mb-4">
    <h1 class="text-2xl font-bold">Create New Invoice</h1>
</div>

<?php
if (isset($_SESSION['error_msg'])) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>{$_SESSION['error_msg']}</div>";
    unset($_SESSION['error_msg']);
}
?>

<form method="POST" action="" id="invoiceForm" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    <div class="mb-6 border-b pb-4">
        <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="customer_id">
                    Select Customer (Optional)
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="customer_id" name="customer_id" onchange="populateCustomerInfo()">
                    <option value="">-- New Customer --</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>" 
                                data-name="<?php echo htmlspecialchars($customer['name']); ?>"
                                data-contact="<?php echo htmlspecialchars($customer['contact_person'] . ' | ' . $customer['phone'] . ' | ' . $customer['email']); ?>">
                            <?php echo htmlspecialchars($customer['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="customer_name">
                    Customer Name *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="customer_name" name="customer_name" type="text" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="customer_contact">
                    Customer Contact Information *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="customer_contact" name="customer_contact" type="text" required>
            </div>
        </div>
    </div>
    
    <div class="mb-6 border-b pb-4">
        <h2 class="text-lg font-semibold mb-4">Invoice Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="invoice_date">
                    Invoice Date *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="invoice_date" name="invoice_date" type="date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="due_date">
                    Due Date *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="due_date" name="due_date" type="date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
            </div>
        </div>
    </div>
    
    <div class="mb-6">
        <h2 class="text-lg font-semibold mb-4">Invoice Items</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white" id="itemsTable">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Type</th>
                        <th class="py-3 px-6 text-left">Description</th>
                        <th class="py-3 px-6 text-center">Quantity</th>
                        <th class="py-3 px-6 text-right">Unit Price</th>
                        <th class="py-3 px-6 text-right">Total</th>
                        <th class="py-3 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <tr class="border-b border-gray-200">
                        <td class="py-3 px-6 text-left">
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" name="item_type[]" required>
                                <option value="egg">Egg</option>
                                <option value="bird">Bird</option>
                                <option value="other">Other</option>
                            </select>
                        </td>
                        <td class="py-3 px-6 text-left">
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" name="item_description[]" required>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline item-quantity" type="number" name="item_quantity[]" min="1" value="1" required onchange="calculateItemTotal(this)">
                        </td>
                        <td class="py-3 px-6 text-right">
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline item-price" type="number" name="item_price[]" min="0.01" step="0.01" required onchange="calculateItemTotal(this)">
                        </td>
                        <td class="py-3 px-6 text-right">
                            <span class="item-total">0.00</span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <button type="button" class="text-red-500 hover:text-red-700" onclick="removeItem(this)">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="py-3 px-6">
                            <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded text-sm" onclick="addItem()">
                                <i class="fas fa-plus mr-1"></i> Add Item
                            </button>
                        </td>
                    </tr>
                    <tr class="border-t-2 border-gray-300 font-bold">
                        <td colspan="4" class="py-3 px-6 text-right">Total:</td>
                        <td class="py-3 px-6 text-right" id="invoiceTotal">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <div class="mb-6">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="notes">
            Notes
        </label>
        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="notes" name="notes" rows="3"></textarea>
    </div>
    
    <div class="flex items-center justify-between">
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
            Create Invoice
        </button>
        <a href="invoices.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
            Cancel
        </a>
    </div>
</form>

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
    
    calculateInvoiceTotal();
}

function calculateInvoiceTotal() {
    const totals = document.querySelectorAll('.item-total');
    let invoiceTotal = 0;
    
    totals.forEach(total => {
        invoiceTotal += parseFloat(total.textContent) || 0;
    });
    
    document.getElementById('invoiceTotal').textContent = invoiceTotal.toFixed(2);
}

function addItem() {
    const tbody = document.getElementById('itemsBody');
    const newRow = document.createElement('tr');
    newRow.className = 'border-b border-gray-200';
    
    newRow.innerHTML = `
        <td class="py-3 px-6 text-left">
            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" name="item_type[]" required>
                <option value="egg">Egg</option>
                <option value="bird">Bird</option>
                <option value="other">Other</option>
            </select>
        </td>
        <td class="py-3 px-6 text-left">
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" name="item_description[]" required>
        </td>
        <td class="py-3 px-6 text-center">
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline item-quantity" type="number" name="item_quantity[]" min="1" value="1" required onchange="calculateItemTotal(this)">
        </td>
        <td class="py-3 px-6 text-right">
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline item-price" type="number" name="item_price[]" min="0.01" step="0.01" required onchange="calculateItemTotal(this)">
        </td>
        <td class="py-3 px-6 text-right">
            <span class="item-total">0.00</span>
        </td>
        <td class="py-3 px-6 text-center">
            <button type="button" class="text-red-500 hover:text-red-700" onclick="removeItem(this)">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
}

function removeItem(button) {
    const tbody = document.getElementById('itemsBody');
    
    // Don't remove if it's the only row
    if (tbody.children.length > 1) {
        const row = button.closest('tr');
        row.remove();
        calculateInvoiceTotal();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    populateCustomerInfo();
});
</script>

<?php require_once '../../includes/footer.php'; ?>

