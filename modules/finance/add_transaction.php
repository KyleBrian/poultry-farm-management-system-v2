<?php
/**
 * File: modules/finance/add_transaction.php
 * Add new financial transaction
 * @version 1.0.1
 * @integration_verification PMSFV-021
 */
$page_title = "Add Financial Transaction";
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
        // Validate inputs
        $transaction_type = $_POST['transaction_type'];
        $account_id = $_POST['account_id'];
        $amount = floatval($_POST['amount']);
        $transaction_date = $_POST['transaction_date'];
        $description = $_POST['description'];
        $reference_number = $_POST['reference_number'];
        $payment_method = $_POST['payment_method'];
        
        if ($amount <= 0) {
            throw new Exception("Amount must be greater than zero.");
        }
        
        // Insert transaction
        $stmt = $pdo->prepare("
            INSERT INTO financial_transactions (
                transaction_date, transaction_type, amount, 
                account_id, reference_number, description, 
                payment_method, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?)
        ");
        
        $stmt->execute([
            $transaction_date,
            $transaction_type,
            $amount,
            $account_id,
            $reference_number,
            $description,
            $payment_method,
            $_SESSION['user_id']
        ]);
        
        $_SESSION['success_msg'] = "Transaction added successfully.";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_msg'] = $e->getMessage();
    }
}

// Get accounts for dropdown
$stmt = $pdo->query("SELECT id, account_code, account_name, account_type FROM chart_of_accounts ORDER BY account_code");
$accounts = $stmt->fetchAll();

// Group accounts by type
$grouped_accounts = [];
foreach ($accounts as $account) {
    $grouped_accounts[$account['account_type']][] = $account;
}
?>

<div class="mb-4">
    <h1 class="text-2xl font-bold">Add Financial Transaction</h1>
</div>

<?php
if (isset($_SESSION['error_msg'])) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>{$_SESSION['error_msg']}</div>";
    unset($_SESSION['error_msg']);
}
?>

<form method="POST" action="" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="transaction_type">
            Transaction Type *
        </label>
        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="transaction_type" name="transaction_type" required onchange="updateAccountOptions()">
            <option value="">Select Transaction Type</option>
            <option value="income">Income</option>
            <option value="expense">Expense</option>
            <option value="transfer">Transfer</option>
        </select>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="account_id">
            Account *
        </label>
        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="account_id" name="account_id" required>
            <option value="">Select Account</option>
            <?php foreach ($grouped_accounts as $type => $accounts): ?>
                <optgroup label="<?php echo ucfirst($type); ?>">
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>" data-type="<?php echo $account['account_type']; ?>">
                            <?php echo $account['account_code'] . ' - ' . $account['account_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="amount">
            Amount *
        </label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="amount" name="amount" type="number" step="0.01" min="0.01" required>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="transaction_date">
            Transaction Date *
        </label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="transaction_date" name="transaction_date" type="date" value="<?php echo date('Y-m-d'); ?>" required>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
            Description *
        </label>
        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="description" name="description" rows="3" required></textarea>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="reference_number">
            Reference Number
        </label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="reference_number" name="reference_number" type="text">
    </div>
    
    <div class="mb-6">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="payment_method">
            Payment Method *
        </label>
        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="payment_method" name="payment_method" required>
            <option value="">Select Payment Method</option>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="check">Check</option>
            <option value="credit_card">Credit Card</option>
            <option value="mobile_money">Mobile Money</option>
        </select>
    </div>
    
    <div class="flex items-center justify-between">
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
            Add Transaction
        </button>
        <a href="index.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
            Cancel
        </a>
    </div>
</form>

<script>
function updateAccountOptions() {
    const transactionType = document.getElementById('transaction_type').value;
    const accountSelect = document.getElementById('account_id');
    const options = accountSelect.querySelectorAll('option');
    
    // Reset all options
    for (let i = 0; i < options.length; i++) {
        options[i].style.display = '';
    }
    
    // If no transaction type is selected, return
    if (!transactionType) return;
    
    // Filter options based on transaction type
    for (let i = 0; i < options.length; i++) {
        const option = options[i];
        const accountType = option.getAttribute('data-type');
        
        if (!accountType) continue; // Skip the default option
        
        if (transactionType === 'income' && !['revenue', 'asset'].includes(accountType)) {
            option.style.display = 'none';
        } else if (transactionType === 'expense' && !['expense', 'asset'].includes(accountType)) {
            option.style.display = 'none';
        }
    }
    
    // Reset selected option if it's now hidden
    if (accountSelect.selectedOptions[0] && accountSelect.selectedOptions[0].style.display === 'none') {
        accountSelect.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', updateAccountOptions);
</script>

<?php require_once '../../includes/footer.php'; ?>

