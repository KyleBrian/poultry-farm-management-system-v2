<?php
/**
 * File: modules/finance/budgets.php
 * Budget management
 * @version 1.0.1
 * @integration_verification PMSFV-024
 */
$page_title = "Budget Management";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Check if user has appropriate permissions
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    $_SESSION['error_msg'] = "You don't have permission to access this page.";
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Get current year and month
$current_year = date('Y');
$current_month = date('m');

// Get selected year and month from query parameters
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : $current_month;

// Fetch budget data for selected year and month
$stmt = $pdo->prepare("
    SELECT b.*, ca.account_name, ca.account_type
    FROM budgets b
    JOIN chart_of_accounts ca ON b.account_id = ca.id
    WHERE b.year = ? AND b.month = ?
    ORDER BY ca.account_type, ca.account_name
");
$stmt->execute([$selected_year, $selected_month]);
$budgets = $stmt->fetchAll();

// Group budgets by account type
$grouped_budgets = [];
foreach ($budgets as $budget) {
    $grouped_budgets[$budget['account_type']][] = $budget;
}

// Calculate budget totals
$income_budget = 0;
$expense_budget = 0;
foreach ($budgets as $budget) {
    if ($budget['account_type'] == 'revenue') {
        $income_budget += $budget['amount'];
    } elseif ($budget['account_type'] == 'expense') {
        $expense_budget += $budget['amount'];
    }
}
$net_budget = $income_budget - $expense_budget;

// Get actual data for the selected period
$start_date = "$selected_year-$selected_month-01";
$end_date = date('Y-m-t', strtotime($start_date));

$stmt = $pdo->prepare("
    SELECT ft.account_id, ca.account_name, ca.account_type, SUM(ft.amount) as actual_amount
    FROM financial_transactions ft
    JOIN chart_of_accounts ca ON ft.account_id = ca.id
    WHERE ft.transaction_date BETWEEN ? AND ?
    GROUP BY ft.account_id
");
$stmt->execute([$start_date, $end_date]);
$actuals = $stmt->fetchAll();

// Create lookup array for actual amounts
$actual_amounts = [];
foreach ($actuals as $actual) {
    $actual_amounts[$actual['account_id']] = $actual['actual_amount'];
}

// Calculate actual totals
$income_actual = 0;
$expense_actual = 0;
foreach ($actuals as $actual) {
    if ($actual['account_type'] == 'revenue') {
        $income_actual += $actual['actual_amount'];
    } elseif ($actual['account_type'] == 'expense') {
        $expense_actual += $actual['actual_amount'];
    }
}
$net_actual = $income_actual - $expense_actual;
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Budget Management</h1>
    <a href="create_budget.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
        <i class="fas fa-plus mr-2"></i>Create Budget
    </a>
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

<!-- Period Selector -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" action="" class="flex flex-wrap items-center gap-4">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="year">
                Year
            </label>
            <select class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="year" name="year" onchange="this.form.submit()">
                <?php for ($year = $current_year - 2; $year <= $current_year + 2; $year++): ?>
                    <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="month">
                Month
            </label>
            <select class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="month" name="month" onchange="this.form.submit()">
                <?php for ($month = 1; $month <= 12; $month++): ?>
                    <option value="<?php echo $month; ?>" <?php echo $month == $selected_month ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $month, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
    </form>
</div>

<!-- Budget Summary -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Income</h2>
        <div class="flex justify-between">
            <div>
                <p class="text-sm text-gray-600">Budget</p>
                <p class="text-2xl font-bold text-green-600"><?php echo formatCurrency($income_budget); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Actual</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo formatCurrency($income_actual); ?></p>
            </div>
        </div>
        <div class="mt-2">
            <?php 
            $income_variance = $income_actual - $income_budget;
            $income_variance_percent = $income_budget > 0 ? ($income_variance / $income_budget) * 100 : 0;
            $income_variance_class = $income_variance >= 0 ? 'text-green-600' : 'text-red-600';
            ?>
            <p class="text-sm text-gray-600">Variance: <span class="<?php echo $income_variance_class; ?>"><?php echo formatCurrency($income_variance); ?> (<?php echo number_format($income_variance_percent, 1); ?>%)</span></p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Expenses</h2>
        <div class="flex justify-between">
            <div>
                <p class="text-sm text-gray-600">Budget</p>
                <p class="text-2xl font-bold text-red-600"><?php echo formatCurrency($expense_budget); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Actual</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo formatCurrency($expense_actual); ?></p>
            </div>
        </div>
        <div class="mt-2">
            <?php 
            $expense_variance = $expense_budget - $expense_actual;
            $expense_variance_percent = $expense_budget > 0 ? ($expense_variance / $expense_budget) * 100 : 0;
            $expense_variance_class = $expense_variance >= 0 ? 'text-green-600' : 'text-red-600';
            ?>
            <p class="text-sm text-gray-600">Variance: <span class="<?php echo $expense_variance_class; ?>"><?php echo formatCurrency($expense_variance); ?> (<?php echo number_format($expense_variance_percent, 1); ?>%)</span></p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Net Profit</h2>
        <div class="flex justify-between">
            <div>
                <p class="text-sm text-gray-600">Budget</p>
                <p class="text-2xl font-bold <?php echo $net_budget >= 0 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo formatCurrency($net_budget); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Actual</p>
                <p class="text-2xl font-bold <?php echo $net_actual >= 0 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo formatCurrency($net_actual); ?></p>
            </div>
        </div>
        <div class="mt-2">
            <?php 
            $net_variance = $net_actual - $net_budget;
            $net_variance_percent = $net_budget != 0 ? ($net_variance / abs($net_budget)) * 100 : 0;
            $net_variance_class = $net_variance >= 0 ? 'text-green-600' : 'text-red-600';
            ?>
            <p class="text-sm text-gray-600">Variance: <span class="<?php echo $net_variance_class; ?>"><?php echo formatCurrency($net_variance); ?> (<?php echo number_format($net_variance_percent, 1); ?>%)</span></p>
        </div>
    </div>
</div>

<!-- Budget Details -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Budget Details</h2>
    
    <?php if (count($budgets) > 0): ?>
        <?php if (isset($grouped_budgets['revenue'])): ?>
            <h3 class="text-md font-semibold mb-2 text-green-600">Income</h3>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">Account</th>
                            <th class="py-3 px-6 text-right">Budget</th>
                            <th class="py-3 px-6 text-right">Actual</th>
                            <th class="py-3 px-6 text-right">Variance</th>
                            <th class="py-3 px-6 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                        <?php foreach ($grouped_budgets['revenue'] as $budget): ?>
                            <?php 
                            $actual = $actual_amounts[$budget['account_id']] ?? 0;
                            $variance = $actual - $budget['amount'];
                            $variance_percent = $budget['amount'] > 0 ? ($variance / $budget['amount']) * 100 : 0;
                            $variance_class = $variance >= 0 ? 'text-green-600' : 'text-red-600';
                            ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-6 text-left">
                                    <?php echo htmlspecialchars($budget['account_name']); ?>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <?php echo formatCurrency($budget['amount']); ?>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <?php echo formatCurrency($actual); ?>
                                </td>
                                <td class="py-3 px-6 text-right <?php echo $variance_class; ?>">
                                    <?php echo formatCurrency($variance); ?> (<?php echo number_format($variance_percent, 1); ?>%)
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex item-center justify-center">
                                        <a href="edit_budget.php?id=<?php echo $budget['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if (isset($grouped_budgets['expense'])): ?>
            <h3 class="text-md font-semibold mb-2 text-red-600">Expenses</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">Account</th>
                            <th class="py-3 px-6 text-right">Budget</th>
                            <th class="py-3 px-6 text-right">Actual</th>
                            <th class="py-3 px-6 text-right">Variance</th>
                            <th class="py-3 px-6 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                        <?php foreach ($grouped_budgets['expense'] as $budget): ?>
                            <?php 
                            $actual = $actual_amounts[$budget['account_id']] ?? 0;
                            $variance = $budget['amount'] - $actual;
                            $variance_percent = $budget['amount'] > 0 ? ($variance / $budget['amount']) * 100 : 0;
                            $variance_class = $variance >= 0 ? 'text-green-600' : 'text-red-600';
                            ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-6 text-left">
                                    <?php echo htmlspecialchars($budget['account_name']); ?>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <?php echo formatCurrency($budget['amount']); ?>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <?php echo formatCurrency($actual); ?>
                                </td>
                                <td class="py-3 px-6 text-right <?php echo $variance_class; ?>">
                                    <?php echo formatCurrency($variance); ?> (<?php echo number_format($variance_percent, 1); ?>%)
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex item-center justify-center">
                                        <a href="edit_budget.php?id=<?php echo $budget['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
            <p>No budget has been set for this period. <a href="create_budget.php?year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>" class="underline">Create a budget now</a>.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>

