<?php
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Check if sale ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid sale ID";
    header("Location: index.php");
    exit();
}

$sale_id = $_GET['id'];

// Get sale details
$sale_query = "SELECT s.*, c.name as customer_name, c.contact as customer_contact, c.email as customer_email, 
               u.username as created_by 
               FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.id 
               LEFT JOIN users u ON s.created_by = u.id 
               WHERE s.id = ?";
$stmt = $conn->prepare($sale_query);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale_result = $stmt->get_result();

if ($sale_result->num_rows == 0) {
    $_SESSION['error'] = "Sale not found";
    header("Location: index.php");
    exit();
}

$sale = $sale_result->fetch_assoc();

// Get sale items
$items_query = "SELECT si.*, p.name as product_name, p.unit as product_unit 
                FROM sale_items si 
                LEFT JOIN products p ON si.product_id = p.id 
                WHERE si.sale_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$items_result = $stmt->get_result();
$sale_items = [];
while ($item = $items_result->fetch_assoc()) {
    $sale_items[] = $item;
}

// Get payment history
$payments_query = "SELECT * FROM payments WHERE sale_id = ? ORDER BY payment_date DESC";
$stmt = $conn->prepare($payments_query);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$payments_result = $stmt->get_result();
$payments = [];
while ($payment = $payments_result->fetch_assoc()) {
    $payments[] = $payment;
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Sale Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Sales</a></li>
                        <li class="breadcrumb-item active">View Sale</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php include '../../includes/alerts.php'; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-invoice"></i> Invoice #<?php echo $sale['invoice_number']; ?>
                            </h3>
                            <div class="card-tools">
                                <a href="edit.php?id=<?php echo $sale_id; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="print_invoice.php?id=<?php echo $sale_id; ?>" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-print"></i> Print
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Customer Information</h5>
                                    <p><strong>Name:</strong> <?php echo $sale['customer_name']; ?></p>
                                    <p><strong>Contact:</strong> <?php echo $sale['customer_contact']; ?></p>
                                    <p><strong>Email:</strong> <?php echo $sale['customer_email']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Sale Information</h5>
                                    <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($sale['sale_date'])); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge <?php echo ($sale['status'] == 'Paid') ? 'badge-success' : (($sale['status'] == 'Partial') ? 'badge-warning' : 'badge-danger'); ?>">
                                            <?php echo $sale['status']; ?>
                                        </span>
                                    </p>
                                    <p><strong>Created By:</strong> <?php echo $sale['created_by']; ?></p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5>Sale Items</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $counter = 1;
                                        foreach ($sale_items as $item): 
                                        ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo $item['product_name']; ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo $item['product_unit']; ?></td>
                                            <td><?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="5" class="text-right">Subtotal:</th>
                                            <th><?php echo number_format($sale['subtotal'], 2); ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="5" class="text-right">Tax (<?php echo $sale['tax_rate']; ?>%):</th>
                                            <th><?php echo number_format($sale['tax_amount'], 2); ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="5" class="text-right">Discount:</th>
                                            <th><?php echo number_format($sale['discount'], 2); ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="5" class="text-right">Grand Total:</th>
                                            <th><?php echo number_format($sale['total_amount'], 2); ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="5" class="text-right">Amount Paid:</th>
                                            <th><?php echo number_format($sale['amount_paid'], 2); ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="5" class="text-right">Balance Due:</th>
                                            <th><?php echo number_format($sale['total_amount'] - $sale['amount_paid'], 2); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Payment History</h5>
                                    <?php if (count($payments) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Reference</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td><?php echo date('F d, Y', strtotime($payment['payment_date'])); ?></td>
                                                    <td><?php echo number_format($payment['amount'], 2); ?></td>
                                                    <td><?php echo $payment['payment_method']; ?></td>
                                                    <td><?php echo $payment['reference']; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <p>No payment records found.</p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Add Payment</h5>
                                    <?php if ($sale['status'] != 'Paid'): ?>
                                    <form action="process_payment.php" method="post">
                                        <input type="hidden" name="sale_id" value="<?php echo $sale_id; ?>">
                                        <div class="form-group">
                                            <label for="amount">Amount</label>
                                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required max="<?php echo $sale['total_amount'] - $sale['amount_paid']; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="payment_method">Payment Method</label>
                                            <select class="form-control" id="payment_method" name="payment_method" required>
                                                <option value="Cash">Cash</option>
                                                <option value="Bank Transfer">Bank Transfer</option>
                                                <option value="Mobile Money">Mobile Money</option>
                                                <option value="Check">Check</option>
                                                <option value="Credit Card">Credit Card</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="reference">Reference (Optional)</label>
                                            <input type="text" class="form-control" id="reference" name="reference">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Record Payment</button>
                                    </form>
                                    <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> This invoice has been fully paid.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Notes</h5>
                                    <p><?php echo !empty($sale['notes']) ? nl2br($sale['notes']) : 'No notes available'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this sale? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form action="delete.php" method="post">
                    <input type="hidden" name="sale_id" value="<?php echo $sale_id; ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>

