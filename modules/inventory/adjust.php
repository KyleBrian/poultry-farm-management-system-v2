<?php
$page_title = "Adjust Inventory";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$item_id = intval($_GET['id']);

// Fetch item details
$stmt = $pdo->prepare("
    SELECT i.*, c.category_name 
    FROM inventory_items i 
    LEFT JOIN inventory_categories c ON i.category_id = c.id 
    WHERE i.id = ?
");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    $_SESSION['error_msg'] = "Inventory item not found.";
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $transaction_type = $_POST['transaction_type'];
        $quantity = floatval($_POST['quantity']);
        $unit_price = isset($_POST['unit_price']) ? floatval($_POST['unit_price']) : null;
        $notes = $_POST['notes'];
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Update inventory quantity
        if ($transaction_type == 'purchase' || $transaction_type == 'adjustment') {
            $new_quantity = $item['current_quantity'] + $quantity;
        } else {
            $new_quantity = $item['current_quantity'] - $quantity;
            
            // Check if we have enough quantity
            if ($new_quantity < 0) {
                throw new Exception("Not enough quantity available. Current quantity: " . $item['current_quantity']);
            }
        }
        
        $stmt = $pdo->prepare("UPDATE inventory_items SET current_quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $item_id]);
        
        // Record transaction
        $stmt = $pdo->prepare("
            INSERT INTO inventory_transactions (
                item_id, transaction_type, quantity, 
                unit_price, transaction_date, notes, created_by
            ) VALUES (?, ?, ?, ?, NOW(), ?, ?)
        ");
        $stmt->execute([
            $item_id,
            $transaction_type,
            $quantity,
            $unit_price,
            $notes,
            $_SESSION['user_id']
        ]);
        
        // If it's a purchase, update the unit cost
        if ($transaction_type == 'purchase' && $unit_price > 0) {
            $stmt = $pdo->prepare("UPDATE inventory_items SET unit_cost = ? WHERE id = ?");
            $stmt->execute([$unit_price, $item_id]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_msg'] = "Inventory adjusted successfully.";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error_msg'] = $e->getMessage();
    }
}
?>

<div class="mb-4">
    <h1 class="text-2xl font-bold">Adjust Inventory: <?php echo htmlspecialchars($item['item_name']); ?></h1>
</div>

<?php
if (isset($_SESSION['error_msg'])) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>{$_SESSION['error_msg']}</div>";
    unset($_SESSION['error_msg']);
}
?>

<div class="bg-white shadow-md rounded p-6 mb-4">
    <div class="mb-4">
        <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category_name']); ?></p>
        <p><strong>Current Quantity:</strong> <?php echo number_format($item['current_quantity'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></p>
        <p><strong>Unit Cost:</strong> <?php echo formatCurrency($item['unit_cost']); ?></p>
    </div>
</div>

<form method="POST" action="" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="transaction_type">
            Transaction Type *
        </label>
        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="transaction_type" name="transaction_type" required onchange="toggleUnitPrice()">
            <option value="purchase">Purchase (Add to Inventory)</option>
            <option value="consumption">Consumption (Remove from Inventory)</option>
            <option value="adjustment">Adjustment (Add to Inventory)</option>
            <option value="transfer">Transfer (Remove from Inventory)</option>
        </select>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity">
            Quantity *
        </label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="quantity" name="quantity" type="number" step="0.01" min="0.01" required>
        <p class="text-xs text-gray-500 mt-1">Unit: <?php echo htmlspecialchars($item['unit_of_measure']); ?></p>
    </div>
    
    <div class="mb-4" id="unit_price_container">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="unit_price">
            Unit Price
        </label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="unit_price" name="unit_price" type="number" step="0.01" min="0">
    </div>
    
    <div class="mb-6">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="notes">
            Notes
        </label>
        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="notes" name="notes" rows="3"></textarea>
    </div>
    
    <div class="flex items-center justify-between">
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
            Submit
        </button>
        <a href="index.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
            Cancel
        </a>
    </div>
</form>

<script>
function toggleUnitPrice() {
    const transactionType = document.getElementById('transaction_type').value;
    const unitPriceContainer = document.getElementById('unit_price_container');
    
    if (transactionType === 'purchase') {
        unitPriceContainer.style.display = 'block';
    } else {
        unitPriceContainer.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleUnitPrice);
</script>

<?php require_once '../../includes/footer.php'; ?>

