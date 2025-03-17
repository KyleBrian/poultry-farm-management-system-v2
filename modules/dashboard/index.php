<?php
$page_title = "Dashboard";
require_once '../../includes/header.php';

// Fetch dashboard data
$stmt = $pdo->query("SELECT COUNT(*) as total_flocks, SUM(current_quantity) as total_birds FROM flocks");
$flock_data = $stmt->fetch();

$stmt = $pdo->query("SELECT SUM(quantity) as total_eggs FROM egg_production WHERE DATE(collection_date) = CURDATE()");
$egg_data = $stmt->fetch();

$stmt = $pdo->query("SELECT SUM(quantity_kg) as total_feed FROM feed_inventory");
$feed_data = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as total_employees FROM employees WHERE status = 'active'");
$employee_data = $stmt->fetch();

// Fetch last 7 days egg production
$stmt = $pdo->query("
    SELECT DATE(collection_date) as date, SUM(quantity) as total_eggs 
    FROM egg_production 
    WHERE collection_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(collection_date)
    ORDER BY DATE(collection_date)
");
$egg_production_data = $stmt->fetchAll();

// Fetch financial summary
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as total_expense
    FROM financial_transactions
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$financial_data = $stmt->fetch();

?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-teal-600 bg-opacity-75">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="mb-2 text-sm font-medium text-gray-600">Total Birds</p>
                <p class="text-lg font-semibold text-gray-700"><?php echo number_format($flock_data['total_birds']); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-600 bg-opacity-75">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="mb-2 text-sm font-medium text-gray-600">Today's Egg Production</p>
                <p class="text-lg font-semibold text-gray-700"><?php echo number_format($egg_data['total_eggs']); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-600 bg-opacity-75">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="mb-2 text-sm font-medium text-gray-600">Feed in Stock</p>
                <p class="text-lg font-semibold text-gray-700"><?php echo number_format($feed_data['total_feed'], 2); ?> kg</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-600 bg-opacity-75">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="mb-2 text-sm font-medium text-gray-600">Total Employees</p>
                <p class="text-lg font-semibold text-gray-700"><?php echo number_format($employee_data['total_employees']); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Egg Production (Last 7 Days)</h2>
        <canvas id="eggProductionChart"></canvas>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Financial Summary (Last 30 Days)</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Income</p>
                <p class="text-2xl font-semibold text-green-600"><?php echo formatCurrency($financial_data['total_income']); ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Total Expenses</p>
                <p class="text-2xl font-semibold text-red-600"><?php echo formatCurrency($financial_data['total_expense']); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-sm font-medium text-gray-600">Net Profit</p>
            <p class="text-2xl font-semibold <?php echo ($financial_data['total_income'] - $financial_data['total_expense'] >= 0) ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo formatCurrency($financial_data['total_income'] - $financial_data['total_expense']); ?>
            </p>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="<?php echo BASE_URL; ?>modules/egg_production/add.php" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded text-center">
            Record Egg Production
        </a>
        <a href="<?php echo BASE_URL; ?>modules/feed_consumption/add.php" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded text-center">
            Record Feed Consumption
        </a>
        <a href="<?php echo BASE_URL; ?>modules/bird_health/add.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-center">
            Record Health Issue
        </a>
        <a href="<?php echo BASE_URL; ?>modules/finance/add.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-center">
            Add Financial Transaction
        </a>
    </div>
</div>

<script>
    // Egg Production Chart
    var ctx = document.getElementById('eggProductionChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($egg_production_data, 'date')); ?>,
            datasets: [{
                label: 'Eggs Collected',
                data: <?php echo json_encode(array_column($egg_production_data, 'total_eggs')); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Eggs'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>

