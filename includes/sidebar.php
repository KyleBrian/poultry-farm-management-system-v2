<!-- Sidebar -->
<div class="bg-gray-800 text-white w-64 min-h-screen flex-shrink-0 hidden md:block">
    <div class="p-4 flex items-center">
        <i class="fas fa-feather-alt text-2xl mr-2"></i>
        <span class="text-xl font-bold">Poultry Manager</span>
    </div>
    
    <nav class="mt-6">
        <div class="px-4 py-2 text-xs text-gray-400 uppercase">Main</div>
        
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="flex items-center px-4 py-3 hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gray-700' : ''; ?>">
            <i class="fas fa-tachometer-alt w-6"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>modules/flock/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 <?php echo strpos($_SERVER['PHP_SELF'], 'modules/flock/') !== false ? 'bg-gray-700' : ''; ?>">
            <i class="fas fa-feather-alt w-6"></i>
            <span>Flock Management</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>modules/egg_production/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 <?php echo strpos($_SERVER['PHP_SELF'], 'modules/egg_production/') !== false ? 'bg-gray-700' : ''; ?>">
            <i class="fas fa-egg w-6"></i>
            <span>Egg Production</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>modules/feed/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 <?php echo strpos($_SERVER['PHP_SELF'], 'modules/feed/') !== false ? 'bg-gray-700' : ''; ?>">
            <i class="fas fa-wheat-awn w-6"></i>
            <span>Feed Management</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>modules/health/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 <?php echo strpos($_SERVER['PHP_SELF'], 'modules/health/') !== false ? 'bg-gray-700' : ''; ?>">
            <i class="fas fa-stethoscope w-6"></i>
            <span>Health Records</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>modules/sales/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 <?php echo strpos($_SERVER['PHP_SELF'], 'modules/sales/') !== false ? 'bg-gray-700' : ''; ?>">
            <i class="fas fa-shopping-cart w-6"></i>
            <span>Sales & Inventory</span>
        </a>
        
        <div class="px-4 py-2 text-xs text-gray-400 uppercase mt-4">Reports</div>
        
        <a href="<?php echo BASE_URL; ?>modules/reports/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 <?php echo strpos($_SERVER['PHP_SELF'], 'modules/reports/') !== false ? 'bg-gray-700' : ''; ?>">
            <i class="fas fa-chart-bar w-6"></i>
            <span>Reports & Analytics</span>
        </a>
        
        <div class="px-4 py-2 text-xs text-gray-400 uppercase mt-4">Settings</div>
        
        <a href="<?php echo BASE_URL; ?>modules/users/profile.php" class="flex items-center px-4 py-3 hover:bg-gray-700 <?php echo strpos($_SERVER['PHP_SELF'], 'modules/users/profile.php') !== false ? 'bg-gray-700' : ''; ?>">
            <i class="fas fa-user w-6"></i>
            <span>My Profile</span>
        </a>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <a href="<?php echo BASE_URL; ?>modules/users/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 <?php echo strpos($_SERVER['PHP_SELF'], 'modules/users/index.php') !== false ? 'bg-gray-700' : ''; ?>">
            <i class="fas fa-users w-6"></i>
            <span>User Management</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>modules/settings/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 <?php echo strpos($_SERVER['PHP_SELF'], 'modules/settings/') !== false ? 'bg-gray-700' : ''; ?>">
            <i class="fas fa-cog w-6"></i>
            <span>System Settings</span>
        </a>
        <?php endif; ?>
        
        <a href="<?php echo BASE_URL; ?>logout.php" class="flex items-center px-4 py-3 hover:bg-gray-700 mt-4 text-red-400">
            <i class="fas fa-sign-out-alt w-6"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

