<?php
/**
 * File: includes/topnav.php
 * Top navigation bar template
 * @version 1.0.1
 * @integration_verification PMSFV-014
 */
?>
<!-- Top Navigation -->
<div class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <div class="flex items-center md:hidden">
            <button id="sidebarToggle" class="text-gray-600 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
        
        <div class="flex-1 flex justify-end items-center">
            <div class="relative mr-4">
                <input type="text" placeholder="Search..." class="bg-gray-100 rounded-full py-2 px-4 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
            
            <div class="relative mr-4">
                <button class="relative p-2 text-gray-600 hover:text-gray-800 focus:outline-none">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="absolute top-0 right-0 h-4 w-4 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
                </button>
            </div>
            
            <div class="relative">
                <button id="userMenuButton" class="flex items-center focus:outline-none">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=random" alt="User Avatar" class="h-8 w-8 rounded-full mr-2">
                    <span class="hidden md:block text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <i class="fas fa-chevron-down ml-1 text-gray-400"></i>
                </button>
                
                <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 hidden z-10">
                    <a href="<?php echo BASE_URL; ?>modules/users/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user mr-2"></i> My Profile
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/settings/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <div class="border-t border-gray-100"></div>
                    <a href="<?php echo BASE_URL; ?>logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

