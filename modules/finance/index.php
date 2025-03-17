<?php
/**
 * File: modules/finance/index.php
 * Financial management dashboard
 * @version 1.0.1
 * @integration_verification PMSFV-020
 */
$page_title = "Financial Management";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Check if user has appropriate permissions
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    $_SESSION['error_msg'] = "You don't have permission to access this page.";
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Get financial summary for current month
$current_month = date('Y-m');
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as total_expense
    FROM financial_transactions
    WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?
");
$stmt->execute([$current_month]);
$financial_summary = $stmt->fetch();

// Get recent transactions
$stmt = $pdo->query("
    SELECT ft.*, ca.account_name, u.username as created_by_name
    FROM financial_transactions ft
    LEFT JOIN chart_of_accounts ca ON ft.account_id = ca.id
    LEFT JOIN users u ON ft.created_by = u.id
    ORDER BY ft.transaction_date DESC, ft.id DESC
    LIMIT 10
");
$recent_transactions = $stmt->fetchAll();

// Get expense breakdown by category for current month
$stmt = $pdo->prepare("
    SELECT ca.account_name, SUM(ft.amount) as total_amount
    FROM financial_transactions ft
    JOIN chart_of_accounts ca ON ft.account_id = ca.id
    WHERE ft.transaction_type = 'expense'
    AND DATE_FORMAT(ft.transaction_date, '%Y-%m') = ?
    GROUP BY ca.id
    ORDER BY total_amount DESC
");
$stmt->execute([$current_month]);
$expense_breakdown = $stmt->fetchAll();

// Get income breakdown by category for current month
$stmt = $pdo->prepare("
    SELECT ca.account_name, SUM(ft.amount) as total_amount
    FROM financial_transactions ft
    JOIN chart_of_accounts ca ON ft.account_id = ca.id
    WHERE ft.transaction_type = 'income'
    AND DATE_FORMAT(ft.transaction_date, '%Y-%m') = ?
    GROUP BY ca.id
    ORDER BY total_amount DESC
");
$stmt->execute([$current_month]);
$income_breakdown = $stmt->fetchAll();
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Financial Management</h1>
    <div class="flex space-x-2">
        <a href="add_transaction.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i>Add Transaction
        </a>
        <a href="invoices.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-file-invoice-dollar mr-2"></i>Invoices
        </a>
        <a href="budgets.php" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-chart-pie mr-2"></i>Budgets
        </a>
        <a href="accounts.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-book mr-2"></i>Chart of Accounts
        </a>
    </div>
</div>

<?php
if (isset($_SESSION['success_msg'])) {
    echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>{$_SESSION['success_msg']}</div>";
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>{$_SESSION['error_msg']}</div>";
    unset($_SESSION['error_msg']);
}
?>

<!-- Financial Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Income (This Month)</h2>
        <p class="text-3xl font-bold text-green-600"><?php echo formatCurrency($financial_summary['total_income'] ?? 0); ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Expenses (This Month)</h2>
        <p class="text-3xl font-bold text-red-600"><?php echo formatCurrency($financial_summary['total_expense'] ?? 0); ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Net Profit (This Month)</h2>
        <?php 
        $net_profit = ($financial_summary['total_income'] ?? 0) - ($financial_summary['total_expense'] ?? 0);
        $profit_color = $net_profit >= 0 ? 'text-green-600' : 'text-red-600';
        ?>
        <p class="text-3xl font-bold <?php echo $profit_color; ?>"><?php echo formatCurrency($net_profit); ?></p>
    </div>
</div>

<!-- Financial Charts -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- Expense Breakdown Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Expense Breakdown</h2>
        <canvas id="expenseChart" height="250"></canvas>
    </div>
    
    <!-- Income Breakdown Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Income Breakdown</h2>
        <canvas id="incomeChart" height="250"></canvas>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Recent Transactions</h2>
        <a href="transactions.php" class="text-blue-500 hover:text-blue-700">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Date</th>
                    <th class="py-3 px-6 text-left">Description</th>
                    <th class="py-3 px-6 text-left">Account</th>
                    <th class="py-3 px-6 text-center">Type</th>
                    <th class="py-3 px-6 text-right">Amount</th>
                    <th class="py-3 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm">
                <?php if (count($recent_transactions) > 0): ?>
                    <?php foreach ($recent_transactions as $transaction): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap">
                                <?php echo formatDate($transaction['transaction_date']); ?>
                            </td>
                            <td class="py-3 px-6 text-left">
                                <?php echo htmlspecialchars($transaction['description']); ?>
                            </td>
                            <td class="py-3 px-6 text-left">
                                <?php echo htmlspecialchars($transaction['account_name']); ?>
                            </td>
                            <td class="py-3 px-6 text-center">
                                <?php if ($transaction['transaction_type'] == 'income'): ?>
                                    <span class="bg-green-200 text-green-600 py-1 px-3 rounded-full text-xs">Income</span>
                                <?php elseif ($transaction['transaction_type'] == 'expense'): ?>
                                    <span class="bg-red-200 text-red-600 py-1 px-3 rounded-full text-xs">Expense</span>
                                <?php else: ?>
                                    <span class="bg-blue-200 text-blue-600 py-1 px-3 rounded-full text-xs">Transfer</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-6 text-right">
                                <?php echo formatCurrency($transaction['amount']); ?>
                            </td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center">
                                    <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_transaction.php?id=<?php echo $transaction['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_transaction.php?id=<?php echo $transaction['id']; ?>" class="w-4 mr-2 transform hover:text-red-500 hover:scale-110" onclick="return confirm('Are you sure you want to delete this transaction?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="border-b border-gray-200">
                        <td class="py-3 px-6 text-center" colspan="6">No transactions found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Links -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold mb-4">Financial Reports</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="reports/income_statement.php" class="bg-blue-100 hover:bg-blue-200 p-4 rounded-lg flex items-center">
            <i class="fas fa-chart-line text-blue-600 text-2xl mr-3"></i>
            <div>
                <h3 class="font-semibold">Income Statement</h3>
                <p class="text-sm text-gray-600">Profit & Loss Report</p>
            </div>
        </a>
        <a href="reports/balance_sheet.php" class="bg-green-100 hover:bg-green-200 p-4 rounded-lg flex items-center">
            <i class="fas fa-balance-scale text-green-600 text-2xl mr-3"></i>
            <div>
                <h3 class="font-semibold">Balance Sheet</h3>
                <p class="text-sm text-gray-600">Assets & Liabilities</p>
            </div>
        </a>
        <a href="reports/cash_flow.php" class="bg-purple-100 hover:bg-purple-200 p-4 rounded-lg flex items-center">
            <i class="fas fa-money-bill-wave text-purple-600 text-2xl mr-3"></i>
            <div>
                <h3 class="font-semibold">Cash Flow</h3>
                <p class="text-sm text-gray-600">Cash Movement Report</p>
            </div>
        </a>
    </div>
</div>

<script>
// Expense Breakdown Chart
var expenseCtx = document.getElementById('expenseChart').getContext('2d');
var expenseChart = new Chart(expenseCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($expense_breakdown, 'account_name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($expense_breakdown, 'total_amount')); ?>,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var label = context.label || '';
                        var value = context.raw || 0;
                        return label + ': ' + new Intl.NumberFormat('en-US', { 
                            style: 'currency', 
                            currency: 'GHS' 
                        }).format(value);
                    }
                }
            }
        }
    }
});

// Income Breakdown Chart
var incomeCtx = document.getElementById('incomeChart').getContext('2d');
var incomeChart = new Chart(incomeCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($income_breakdown, 'account_name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($income_breakdown, 'total_amount')); ?>,
            backgroundColor: [
                '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56',
                '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var label = context.label || '';
                        var value = context.raw || 0;
                        return label + ': ' + new Intl.NumberFormat('en-US', { 
                            style: 'currency', 
                            currency: 'GHS' 
                        }).format(value);
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>

