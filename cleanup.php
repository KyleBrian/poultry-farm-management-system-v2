<?php
/**
 * Cleanup script to remove unwanted files and folders
 * Run this script once to clean up your project structure
 */

// Start session
session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the essential files and folders for the Poultry Farm Management System
$essential_items = [
    // Core system files
    'index.php',
    'login.php',
    'logout.php',
    'dashboard.php',
    'setup.php',
    'config.php',
    
    // Essential directories
    'config/',
    'includes/',
    'models/',
    'controllers/',
    'views/',
    'assets/',
    'database/',
    'modules/',
    
    // Module directories
    'modules/birds/',
    'modules/feed/',
    'modules/eggs/',
    'modules/sales/',
    'modules/expenses/',
    'modules/employees/',
    'modules/inventory/',
    'modules/reports/',
    'modules/settings/',
    
    // This cleanup script itself
    'cleanup.php'
];

// Function to recursively delete a directory
function delete_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

// Get all files and directories in the current directory
$all_items = scandir('.');
$items_to_delete = [];

// Find items that are not in the essential list
foreach ($all_items as $item) {
    if ($item == '.' || $item == '..' || $item == '.git' || $item == '.gitignore') {
        continue;
    }
    
    $is_essential = false;
    foreach ($essential_items as $essential) {
        if ($item === $essential || (strpos($essential, '/') !== false && strpos($essential, $item . '/') === 0)) {
            $is_essential = true;
            break;
        }
    }
    
    if (!$is_essential) {
        $items_to_delete[] = $item;
    }
}

// Display confirmation page
if (!isset($_POST['confirm'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cleanup Poultry Farm Management System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="card">
                <div class="card-header bg-warning">
                    <h3>Cleanup Confirmation</h3>
                </div>
                <div class="card-body">
                    <p>The following files and directories will be permanently deleted:</p>
                    
                    <?php if (empty($items_to_delete)): ?>
                        <div class="alert alert-info">No unnecessary files or directories found.</div>
                    <?php else: ?>
                        <ul class="list-group mb-4">
                            <?php foreach ($items_to_delete as $item): ?>
                                <li class="list-group-item"><?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="alert alert-danger">
                            <strong>Warning:</strong> This action cannot be undone. Make sure you have a backup if needed.
                        </div>
                        
                        <form method="post" action="">
                            <input type="hidden" name="confirm" value="1">
                            <button type="submit" class="btn btn-danger">Delete Files</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    <?php endif; ?>
                    
                    <?php if (empty($items_to_delete)): ?>
                        <a href="index.php" class="btn btn-primary">Return to Homepage</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Process deletion if confirmed
$success = true;
$errors = [];

foreach ($items_to_delete as $item) {
    $path = './' . $item;
    
    if (is_dir($path)) {
        if (!delete_directory($path)) {
            $success = false;
            $errors[] = "Failed to delete directory: $item";
        }
    } else {
        if (!unlink($path)) {
            $success = false;
            $errors[] = "Failed to delete file: $item";
        }
    }
}

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Complete</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header <?php echo $success ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                <h3><?php echo $success ? 'Cleanup Complete' : 'Cleanup Incomplete'; ?></h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        All unnecessary files and directories have been successfully removed.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Some errors occurred during cleanup:
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <h4 class="mt-4">Next Steps:</h4>
                <ol>
                    <li>Run the setup process again by visiting <a href="setup.php">setup.php</a></li>
                    <li>The database error should now be fixed with the updated code</li>
                    <li>After setup completes, you can delete this cleanup script</li>
                </ol>
                
                <div class="mt-4">
                    <a href="setup.php" class="btn btn-primary">Go to Setup</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

