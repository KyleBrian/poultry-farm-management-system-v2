<?php
/**
 * File: modules/reports/custom_report.php
 * Custom report builder
 * @version 1.0.1
 * @integration_verification PMSFV-026
 */
$page_title = "Custom Report Builder";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Check if user has appropriate permissions
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    $_SESSION['error_msg'] = "You don't have permission to access this page.";
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Get report type from query parameter
$report_type = isset($_GET['type']) ? $_GET['type'] : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate inputs
        $report_name = $_POST['report_name'];
        $report_type = $_POST['report_type'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $selected_fields = isset($_POST['fields']) ? $_POST['fields'] : [];
        $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
        $sort_by = $_POST['sort_by'];
        $sort_order = $_POST['sort_order'];
        $save_template = isset($_POST['save_template']) && $_POST['save_template'] == 1;
        $template_name = $_POST['template_name'] ?? '';
        
        if (empty($report_name)) {
            throw new Exception("Report name is required.");
        }
        
        if (empty($selected_fields)) {
            throw new Exception("Please select at least one field to include in the report.");
        }
        
        if (strtotime($end_date) < strtotime($start_date)) {
            throw new Exception("End date must be after start date.");
        }
        
        // Prepare report parameters
        $parameters = [
            'report_type' => $report_type,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'fields' => $selected_fields,
            'filters' => $filters,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order
        ];
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Save as template if requested
        $template_id = null;
        if ($save_template && !empty($template_name)) {
            $stmt = $pdo->prepare("
                INSERT INTO report_templates (
                    template_name, description, report_type, 
                    template_config, created_by
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $template_name,
                "Custom template for $report_type report",
                $report_type,
                json_encode($parameters),
                $_SESSION['user_id']
            ]);
            
            $template_id = $pdo->lastInsertId();
        }
        
        // Generate report file
        $report_data = generateCustomReport($parameters);
        
        // Create PDF file
        $filename = 'report_' . uniqid() . '.pdf';
        $filepath = UPLOAD_PATH . 'reports/' . $filename;
        
        // Ensure directory exists
        if (!file_exists(UPLOAD_PATH . 'reports/')) {
            mkdir(UPLOAD_PATH . 'reports/', 0777, true);
        }
        
        // Save report record
        $stmt = $pdo->prepare("
            INSERT INTO generated_reports (
                template_id, report_name, parameters, 
                file_path, generated_by
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $template_id,
            $report_name,
            json_encode($parameters),
            $filepath,
            $_SESSION['user_id']
        ]);
        
        $report_id = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_msg'] = "Report generated successfully.";
        header("Location: view_report.php?id=$report_id");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_msg'] = $e->getMessage();
    }
}

// Get available fields based on report type
$available_fields = [];
switch ($report_type) {
    case 'production':
        $available_fields = [
            'collection_date' => 'Collection Date',
            'flock_name' => 'Flock Name',
            'total_eggs' => 'Total Eggs',
            'broken_eggs' => 'Broken Eggs',
            'dirty_eggs' => 'Dirty Eggs',
            'collector_name' => 'Collected By'
        ];
        break;
    case 'financial':
        $available_fields = [
            'transaction_date' => 'Transaction Date',
            'transaction_type' => 'Transaction Type',
            'amount' => 'Amount',
            'account_name' => 'Account',
            'description' => 'Description',
            'payment_method' => 'Payment Method',
            'reference_number' => 'Reference Number',
            'created_by_name' => 'Created By'
        ];
        break;
    case 'inventory':
        $available_fields = [
            'item_name' => 'Item Name',
            'category_name' => 'Category',
            'current_quantity' => 'Current Quantity',
            'unit_of_measure' => 'Unit',
            'unit_cost' => 'Unit Cost',
            'reorder_point' => 'Reorder Point',
            'storage_location' => 'Storage Location'
        ];
        break;
    case 'employee':
        $available_fields = [
            'employee_code' => 'Employee ID',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'position_name' => 'Position',
            'department_name' => 'Department',
            'hire_date' => 'Hire Date',
            'status' => 'Status',
            'salary' => 'Salary'
        ];
        break;
    case 'health':
        $available_fields = [
            'record_date' => 'Record Date',
            'flock_name' => 'Flock Name',
            'record_type' => 'Record Type',
            'symptoms' => 'Symptoms',
            'diagnosis' => 'Diagnosis',
            'treatment' => 'Treatment',
            'medication' => 'Medication',
            'vet_name' => 'Veterinarian',
            'recovery_status' => 'Recovery Status'
        ];
        break;
    case 'sales':
        $available_fields = [
            'sale_date' => 'Sale Date',
            'customer_name' => 'Customer',
            'quantity' => 'Quantity',
            'unit_price' => 'Unit Price',
            'total_amount' => 'Total Amount',
            'payment_method' => 'Payment Method',
            'sold_by_name' => 'Sold By'
        ];
        break;
    default:
        $available_fields = [];
}

// Get sort options based on available fields
$sort_options = $available_fields;

// Get filter options based on report type
$filter_options = [];
switch ($report_type) {
    case 'production':
        $filter_options = [
            'flock_id' => [
                'label' => 'Flock',
                'type' => 'select',
                'options' => getFlockOptions()
            ],
            'collection_date' => [
                'label' => 'Collection Date',
                'type' => 'date'
            ],
            'total_eggs' => [
                'label' => 'Total Eggs',
                'type' => 'number'
            ]
        ];
        break;
    case 'financial':
        $filter_options = [
            'transaction_type' => [
                'label' => 'Transaction Type',
                'type' => 'select',
                'options' => [
                    'income' => 'Income',
                    'expense' => 'Expense',
                    'transfer' => 'Transfer'
                ]
            ],
            'account_id' => [
                'label' => 'Account',
                'type' => 'select',
                'options' => getAccountOptions()
            ],
            'transaction_date' => [
                'label' => 'Transaction Date',
                'type' => 'date'
            ],
            'amount' => [
                'label' => 'Amount',
                'type' => 'number'
            ]
        ];
        break;
    // Add more filter options for other report types
}

// Helper functions to get options for dropdowns
function getFlockOptions() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, name FROM flocks ORDER BY name");
    $options = [];
    while ($row = $stmt->fetch()) {
        $options[$row['id']] = $row['name'];
    }
    return $options;
}

function getAccountOptions() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, account_name FROM chart_of_accounts ORDER BY account_name");
    $options = [];
    while ($row = $stmt->fetch()) {
        $options[$row['id']] = $row['account_name'];
    }
    return $options;
}

// Function to generate custom report
function generateCustomReport($parameters) {
    // This would be implemented to query the database based on parameters
    // and return the report data
    return [];
}
?>

<div class="mb-4">
    <h1 class="text-2xl font-bold">Custom Report Builder</h1>
</div>

<?php
if (isset($_SESSION['error_msg'])) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>{$_SESSION['error_msg']}</div>";
    unset($_SESSION['error_msg']);
}
?>

<form method="POST" action="" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    <div class="mb-6 border-b pb-4">
        <h2 class="text-lg font-semibold mb-4">Report Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="report_name">
                    Report Name *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="report_name" name="report_name" type="text" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="report_type">
                    Report Type *
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="report_type" name="report_type" required onchange="window.location.href='custom_report.php?type=' + this.value">
                    <option value="">Select Report Type</option>
                    <option value="production" <?php echo $report_type == 'production' ? 'selected' : ''; ?>>Production Report</option>
                    <option value="financial" <?php echo $report_type == 'financial' ? 'selected' : ''; ?>>Financial Report</option>
                    <option value="inventory" <?php echo $report_type == 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                    <option value="employee" <?php echo $report_type == 'employee' ? 'selected' : ''; ?>>Employee Report</option>
                    <option value="health" <?php echo $report_type == 'health' ? 'selected' : ''; ?>>Health Report</option>
                    <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                </select>
            </div>
        </div>
    </div>
    
    <?php if (!empty($report_type)): ?>
        <div class="mb-6 border-b pb-4">
            <h2 class="text-lg font-semibold mb-4">Date Range</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="start_date">
                        Start Date *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="start_date" name="start_date" type="date" value="<?php echo date('Y-m-01'); ?>" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="end_date">
                        End Date *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="end_date" name="end_date" type="date" value="<?php echo date('Y-m-t'); ?>" required>
                </div>
            </div>
        </div>
        
        <div class="mb-6 border-b pb-4">
            <h2 class="text-lg font-semibold mb-4">Fields to Include</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($available_fields as $field_key => $field_label): ?>
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="form-checkbox" name="fields[]" value="<?php echo $field_key; ?>" checked>
                            <span class="ml-2"><?php echo $field_label; ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mb-6 border-b pb-4">
            <h2 class="text-lg font-semibold mb-4">Filters</h2>
            <div id="filters-container">
                <!-- Initial filter row -->
                <div class="filter-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-2">
                    <div>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline filter-field" name="filters[0][field]" onchange="updateFilterOperators(this)">
                            <option value="">Select Field</option>
                            <?php foreach ($filter_options as $field_key => $filter): ?>
                                <option value="<?php echo $field_key; ?>" data-type="<?php echo $filter['type']; ?>"><?php echo $filter['label']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline filter-operator" name="filters[0][operator]">
                            <option value="=">Equals</option>
                            <option value="!=">Not Equals</option>
                            <option value=">">Greater Than</option>
                            <option value="<">Less Than</option>
                            <option value=">=">Greater Than or Equal</option>
                            <option value="<=">Less Than or Equal</option>
                            <option value="LIKE">Contains</option>
                        </select>
                    </div>
                    <div>
                        <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline filter-value" name="filters[0][value]">
                    </div>
                    <div>
                        <button type="button" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="removeFilter(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <button type="button" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mt-2" onclick="addFilter()">
                <i class="fas fa-plus mr-1"></i> Add Filter
            </button>
        </div>
        
        <div class="mb-6 border-b pb-4">
            <h2 class="text-lg font-semibold mb-4">Sorting</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="sort_by">
                        Sort By
                    </label>
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="sort_by" name="sort_by">
                        <option value="">Default</option>
                        <?php foreach ($sort_options as $field_key => $field_label): ?>
                            <option value="<?php echo $field_key; ?>"><?php echo $field_label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="sort_order">
                        Sort Order
                    </label>
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="sort_order" name="sort_order">
                        <option value="ASC">Ascending</option>
                        <option value="DESC">Descending</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-4">Save as Template</h2>
            <div class="flex items-center mb-4">
                <input type="checkbox" id="save_template" name="save_template" value="1" class="form-checkbox" onchange="toggleTemplateNameField()">
                <label for="save_template" class="ml-2 text-gray-700">Save this report as a template for future use</label>
            </div>
            <div id="template_name_container" class="hidden">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="template_name">
                    Template Name *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="template_name" name="template_name" type="text">
            </div>
        </div>
        
        <div class="flex items-center justify-between">
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                Generate Report
            </button>
            <a href="index.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Cancel
            </a>
        </div>
    <?php else: ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
            <p>Please select a report type to continue.</p>
        </div>
    <?php endif; ?>
</form>

<script>
let filterCount = 1;

function addFilter() {
    const container = document.getElementById('filters-container');
    const newRow = document.createElement('div');
    newRow.className = 'filter-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-2';
    
    newRow.innerHTML = `
        <div>
            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline filter-field" name="filters[${filterCount}][field]" onchange="updateFilterOperators(this)">
                <option value="">Select Field</option>
                <?php foreach ($filter_options as $field_key => $filter): ?>
                    <option value="<?php echo $field_key; ?>" data-type="<?php echo $filter['type']; ?>"><?php echo $filter['label']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline filter-operator" name="filters[${filterCount}][operator]">
                <option value="=">Equals</option>
                <option value="!=">Not Equals</option>
                <option value=">">Greater Than</option>
                <option value="<">Less Than</option>
                <option value=">=">Greater Than or Equal</option>
                <option value="<=">Less Than or Equal</option>
                <option value="LIKE">Contains</option>
            </select>
        </div>
        <div>
            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline filter-value" name="filters[${filterCount}][value]">
        </div>
        <div>
            <button type="button" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="removeFilter(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(newRow);
    filterCount++;
}

function removeFilter(button) {
    const row = button.closest('.filter-row');
    row.remove();
}

function updateFilterOperators(fieldSelect) {
    const row = fieldSelect.closest('.filter-row');
    const operatorSelect = row.querySelector('.filter-operator');
    const valueInput = row.querySelector('.filter-value');
    const fieldType = fieldSelect.options[fieldSelect.selectedIndex].getAttribute('data-type');
    
    // Reset operator options
    operatorSelect.innerHTML = '';
    
    if (fieldType === 'date') {
        // Date operators
        addOption(operatorSelect, '=', 'Equals');
        addOption(operatorSelect, '!=', 'Not Equals');
        addOption(operatorSelect, '>', 'After');
        addOption(operatorSelect, '<', 'Before');
        addOption(operatorSelect, 'BETWEEN', 'Between');
        
        // Change input type to date
        valueInput.type = 'date';
    } else if (fieldType === 'number') {
        // Number operators
        addOption(operatorSelect, '=', 'Equals');
        addOption(operatorSelect, '!=', 'Not Equals');
        addOption(operatorSelect, '>', 'Greater Than');
        addOption(operatorSelect, '<', 'Less Than');
        addOption(operatorSelect, '>=', 'Greater Than or Equal');
        addOption(operatorSelect, '<=', 'Less Than or Equal');
        addOption(operatorSelect, 'BETWEEN', 'Between');
        
        // Change input type to number
        valueInput.type = 'number';
    } else if (fieldType === 'select') {
        // Select operators
        addOption(operatorSelect, '=', 'Equals');
        addOption(operatorSelect, '!=', 'Not Equals');
        
        // Change input type to text
        valueInput.type = 'text';
    } else {
        // Text operators
        addOption(operatorSelect, '=', 'Equals');
        addOption(operatorSelect, '!=', 'Not Equals');
        addOption(operatorSelect, 'LIKE', 'Contains');
        addOption(operatorSelect, 'NOT LIKE', 'Does Not Contain');
        addOption(operatorSelect, 'STARTS_WITH', 'Starts With');
        addOption(operatorSelect, 'ENDS_WITH', 'Ends With');
        
        // Change input type to text
        valueInput.type = 'text';
    }
}

function addOption(selectElement, value, text) {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = text;
    selectElement.appendChild(option);
}

function toggleTemplateNameField() {
    const checkbox = document.getElementById('save_template');
    const container = document.getElementById('template_name_container');
    
    if (checkbox.checked) {
        container.classList.remove('hidden');
        document.getElementById('template_name').required = true;
    } else {
        container.classList.add('hidden');
        document.getElementById('template_name').required = false;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>

