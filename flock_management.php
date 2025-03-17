<?php
require_once 'config/config.php';
check_login();

// Handle form submission for adding new flock
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_flock'])) {
    $name = sanitize_input($_POST['name']);
    $breed = sanitize_input($_POST['breed']);
    $quantity = intval($_POST['quantity']);
    $acquisition_date = sanitize_input($_POST['acquisition_date']);
    $health_status = sanitize_input($_POST['health_status']);

    $stmt = $pdo->prepare("INSERT INTO flocks (name, breed, initial_quantity, current_quantity, acquisition_date, health_status) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $breed, $quantity, $quantity, $acquisition_date, $health_status])) {
        $_SESSION['success_msg'] = "New flock added successfully.";
    } else {
        $_SESSION['error_msg'] = "Error adding new flock.";
    }
    header("Location: flock_management.php");
    exit();
}

// Fetch all flocks
$stmt = $pdo->query("SELECT * FROM flocks ORDER BY acquisition_date DESC");
$flocks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flock Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Flock Management</h1>
        
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
        
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-xl font-semibold mb-4">Add New Flock</h2>
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                            Flock Name
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="text" name="name" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="breed">
                            Breed
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="breed" type="text" name="breed" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity">
                            Quantity
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="quantity" type="number" name="quantity" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="acquisition_date">
                            Acquisition Date
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="acquisition_date" type="date" name="acquisition_date" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="health_status">
                            Health Status
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="health_status" name="health_status" required>
                            <option value="healthy">Healthy</option>
                            <option value="sick">Sick</option>
                            <option value="quarantined">Quarantined</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="add_flock">
                        Add Flock
                    </button>
                </div>
            </form>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Flock List</h2>
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left py-2">Name</th>
                        <th class="text-left py-2">Breed</th>
                        <th class="text-left py-2">Current Quantity</th>
                        <th class="text-left py-2">Acquisition Date</th>
                        <th class="text-left py-2">Health Status</th>
                        <th class="text-left py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flocks as $flock): ?>
                        <tr>
                            <td class="py-2"><?php echo htmlspecialchars($flock['name']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($flock['breed']); ?></td>
                            <td class="py-2"><?php echo $flock['current_quantity']; ?></td>
                            <td class="py-2"><?php echo format_date($flock['acquisition_date']); ?></td>
                            <td class="py-2"><?php echo ucfirst($flock['health_status']); ?></td>
                            <td class="py-2">
                                <a href="edit_flock.php?id=<?php echo $flock['id']; ?>" class="text-blue-500 hover:text-blue-700">Edit</a>
                                <a href="delete_flock.php?id=<?php echo $flock['id']; ?>" class="text-red-500 hover:text-red-700 ml-2" onclick="return confirm('Are you sure you want to delete this flock?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

