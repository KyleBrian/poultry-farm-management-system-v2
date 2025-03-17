<?php
require_once 'config/config.php';
check_login();

// Handle form submission for adding new egg production record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_production'])) {
    $flock_id = intval($_POST['flock_id']);
    $collection_date = sanitize_input($_POST['collection_date']);
    $total_eggs = intval($_POST['total_eggs']);
    $broken_eggs = intval($_POST['broken_eggs']);
    $notes = sanitize_input($_POST['notes']);

    $stmt = $pdo->prepare("INSERT INTO egg_production (flock_id, collection_date, total_eggs, broken_eggs, notes) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$flock_id, $collection_date, $total_eggs, $broken_eggs, $notes])) {
        $_SESSION['success_msg'] = "Egg production record added successfully.";
    } else {
        $_SESSION['error_msg'] = "Error adding egg production record.";
    }
    header("Location: egg_production.php");
    exit();
}

// Fetch all flocks for the dropdown
$stmt = $pdo->query("SELECT id, name FROM flocks ORDER BY name");
$flocks = $stmt->fetchAll();

// Fetch egg production records
$stmt = $pdo->query("
    SELECT ep.*, f.name as flock_name 
    FROM egg_production ep 
    JOIN flocks f ON ep.flock_id = f.id 
    ORDER BY ep.collection_date DESC
");
$productions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egg Production - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Egg Production</h1>
        
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
            <h2 class="text-xl font-semibold mb-4">Add Egg Production Record</h2>
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="flock_id">
                            Flock
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="flock_id" name="flock_id" required>
                            <?php foreach ($flocks as $flock): ?>
                                <option value="<?php echo $flock['id']; ?>"><?php echo htmlspecialchars($flock['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="collection_date">
                            Collection Date
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="collection_date" type="date" name="collection_date" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="total_eggs">
                            Total Eggs
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="total_eggs" type="number" name="total_eggs" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="broken_eggs">
                            Broken Eggs
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="broken_eggs" type="number" name="broken_eggs" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="notes">
                            Notes
                        </label>
                        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="add_production">
                        Add Record
                    </button>
                </div>
            </form>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Egg Production Records</h2>
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left py-2">Date</th>
                        <th class="text-left py-2">Flock</th>
                        <th class="text-left py-2">Total Eggs</th>
                        <th class="text-left py-2">Broken Eggs</th>
                        <th class="text-left py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productions as $production): ?>
                        <tr>
                            <td class="py-2"><?php echo format_date($production['collection_date']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($production['flock_name']); ?></td>
                            <td class="py-2"><?php echo $production['total_eggs']; ?></td>
                            <td class="py-2"><?php echo $production['broken_eggs']; ?></td>
                            <td class="py-2">
                                <a href="edit_egg_production.php?id=<?php echo $production['id']; ?>" class="text-blue-500 hover:text-blue-700">Edit</a>
                                <a href="delete_egg_production.php?id=<?php echo $production['id']; ?>" class="text-red-500 hover:text-red-700 ml-2" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
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

