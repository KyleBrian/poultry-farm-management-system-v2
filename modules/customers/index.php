<?php
/**
 * File: modules/customers/index.php
 * Customer management dashboard
 * @version 1.0.1
 * @integration_verification PMSFV-053
 */

// Include required files
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Page title
$page_title = "Customer Management";

// Get all customers
$customers = db_query($pdo, "
    SELECT 
        id, 
        name, 
        contact_person, 
        email, 
        phone, 
        address, 
        customer_type, 
        created_at, 
        status
    FROM 
        customers
    ORDER BY 
        name ASC
");

// Get customer statistics
$customer_stats = db_query($pdo, "
    SELECT 
        COUNT(*) as total_customers,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers,
        SUM(CASE WHEN customer_type = 'retail' THEN 1 ELSE 0 END) as retail_customers,
        SUM(CASE WHEN customer_type = 'wholesale' THEN 1 ELSE 0 END) as wholesale_customers,
        SUM(CASE WHEN customer_type = 'distributor' THEN 1 ELSE 0 END) as distributor_customers
    FROM 
        customers
");

// Get top customers by sales
$top_customers = db_query($pdo, "
    SELECT 
        c.id,
        c.name,
        COUNT(s.id) as total_sales,
        SUM(s.total_amount) as total_amount
    FROM 
        customers c
    JOIN 
        sales s ON c.id = s.customer_id
    WHERE 
        s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY 
        c.id
    ORDER BY 
        total_amount DESC
    LIMIT 5
");

// Get recent sales
$recent_sales = db_query($pdo, "
    SELECT 
        s.id,
        s.invoice_number,
        s.sale_date,
        s.total_amount,
        s.payment_status,
        c.name as customer_name
    FROM 
        sales s
    JOIN 
        customers c ON s.customer_id = c.id
    ORDER BY 
        s.sale_date DESC
    LIMIT 10
");

// Include header
include '../../includes/header.php';
?>

<!-- Main Content -->
<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo BASE_URL; ?>dashboard.php">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Customer Management</li>
        </ol>

        <!-- Page Content -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-users me-1"></i>
                Customer Management
                <a href="<?php echo BASE_URL; ?>modules/customers/add.php" class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-plus"></i> Add New Customer
                </a>
            </div>
            <div class="card-body">
                <!-- Customer Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-primary text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Total Customers</h6>
                                        <h2 class="mb-0"><?php echo number_format($customer_stats[0]['total_customers']); ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-success text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Active Customers</h6>
                                        <h2 class="mb-0"><?php echo number_format($customer_stats[0]['active_customers']); ?></h2>
                                    </div>
                                    <i class="fas fa-user-check fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-info text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Retail Customers</h6>
                                        <h2 class="mb-0"><?php echo number_format($customer_stats[0]['retail_customers']); ?></h2>
                                    </div>
                                    <i class="fas fa-shopping-bag fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-warning text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Wholesale Customers</h6>
                                        <h2 class="mb-0"><?php echo number_format($customer_stats[0]['wholesale_customers']); ?></h2>
                                    </div>
                                    <i class="fas fa-truck fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Customers and Recent Sales -->
                <div class="row mb-4">
                    <div class="col-xl-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-trophy me-1"></i>
                                Top Customers (Last 6 Months)
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th>Sales Count</th>
                                                <th>Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($top_customers) > 0): ?>
                                                <?php foreach ($top_customers as $customer): ?>
                                                    <tr>
                                                        <td>
                                                            <a href="<?php echo BASE_URL; ?>modules/customers/view.php?id=<?php echo $customer['id']; ?>">
                                                                <?php echo htmlspecialchars($customer['name']); ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo number_format($customer['total_sales']); ?></td>
                                                        <td><?php echo CURRENCY_SYMBOL . number_format($customer['total_amount'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No sales data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-receipt me-1"></i>
                                Recent Sales
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Invoice</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recent_sales) > 0): ?>
                                                <?php foreach ($recent_sales as $sale): ?>
                                                    <tr>
                                                        <td>
                                                            <a href="<?php echo BASE_URL; ?>modules/sales/view.php?id=<?php echo $sale['id']; ?>">
                                                                <?php echo htmlspecialchars($sale['invoice_number']); ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                                        <td><?php echo CURRENCY_SYMBOL . number_format($sale['total_amount'], 2); ?></td>
                                                        <td>
                                                            <?php 
                                                                $status_class = 'secondary';
                                                                if ($sale['payment_status'] == 'paid') {
                                                                    $status_class = 'success';
                                                                } elseif ($sale['payment_status'] == 'partial') {
                                                                    $status_class = 'warning';
                                                                } elseif ($sale['payment_status'] == 'unpaid') {
                                                                    $status_class = 'danger';
                                                                }
                                                            ?>
                                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                                <?php echo ucfirst($sale['payment_status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No recent sales</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Customer List
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="customersTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($customers) > 0): ?>
                                        <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['contact_person']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                                <td><?php echo ucfirst($customer['customer_type']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo ($customer['status'] == 'active') ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($customer['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="<?php echo BASE_URL; ?>modules/customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>modules/customers/edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $customer['id']; ?>)" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No customers found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this customer? This action cannot be undone and may affect related sales records.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Page specific scripts -->
<script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#customersTable').DataTable({
            order: [[0, 'asc']]
        });
    });

    // Delete confirmation
    function confirmDelete(id) {
        $('#confirmDeleteBtn').attr('href', '<?php echo BASE_URL; ?>modules/customers/delete.php?id=' + id);
        $('#deleteModal').modal('show');
    }
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

