<?php
require_once __DIR__ . '/fiscal_menu.php';

function render($ptApp, $data = []) {
    // Check if we're in fiscal context and have fiscal data
    if (isset($data['sequences']) || isset($data['types'])) {
        return renderFiscalMenu($ptApp, $data);
    }
    // Default fallback - render regular navigation
    return renderMenu($ptApp, 'dashboard', 'main');
}

function renderMenu($ptApp, $currentPage = '', $menuType = 'main') {
    $bo = $ptApp->getDefaultBackOfficeUrl();
    $isMirror = $ptApp->isFiscalMirror();
    
    // Define different menu structures with dynamic content support
    $menus = [
        'main' => [
            [
                'title' => 'Dashboard',
                'url' => './',
                'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z',
                'key' => 'dashboard',
                'badge' => null,
                'load_content' => false
            ],
            [
                'title' => 'Sales',
                'url' => '#',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'key' => 'sales',
                'submenu' => [
                    ['title' => 'New Sale', 'url' => './sales/new', 'key' => 'sales-new'],
                    ['title' => 'Sales History', 'url' => './sales/history', 'key' => 'sales-history'],
                    ['title' => 'Returns', 'url' => './sales/returns', 'key' => 'sales-returns']
                ]
            ],
            [
                'title' => 'Products',
                'url' => '#',
                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                'key' => 'products',
                'submenu' => [
                    ['title' => 'All Products', 'url' => './products/', 'key' => 'products-list'],
                    ['title' => 'Categories', 'url' => './categories/', 'key' => 'categories'],
                    ['title' => 'Add Product', 'url' => './products/new', 'key' => 'products-new']
                ]
            ],
            [
                'title' => 'Customers',
                'url' => './customers/',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'key' => 'customers'
            ],
            [
                'title' => 'Reports',
                'url' => '#',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'key' => 'reports',
                'submenu' => [
                    ['title' => 'Sales Report', 'url' => './reports/sales', 'key' => 'reports-sales'],
                    ['title' => 'Inventory Report', 'url' => './reports/inventory', 'key' => 'reports-inventory'],
                    ['title' => 'Financial Report', 'url' => './reports/financial', 'key' => 'reports-financial']
                ]
            ],
            [
                'title' => 'Fiscal Records',
                'url' => './fiscal/',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'key' => 'fiscal',
                'badge' => 'Admin',
                'load_content' => true,
                'submenu' => [
                    ['title' => 'Dashboard', 'url' => './fiscal/', 'key' => 'fiscal-dashboard', 'load_content' => true, 'ajax_url' => './fiscal/'],
                    ['title' => 'Export Records', 'url' => './fiscal/export', 'key' => 'fiscal-export', 'load_content' => true, 'ajax_url' => './fiscal/export'],
                    ['title' => 'Tickets List', 'url' => '#', 'key' => 'fiscal-tickets', 'load_content' => true, 'ajax_url' => './fiscal/sequence/1/tickets/'],
                    ['title' => 'Z Tickets', 'url' => '#', 'key' => 'fiscal-z-tickets', 'load_content' => true, 'ajax_url' => './fiscal/z/1'],
                    ['title' => 'Help - Tickets', 'url' => './fiscal/help/tickets', 'key' => 'fiscal-help-tickets', 'load_content' => true, 'ajax_url' => './fiscal/help/tickets'],
                    ['title' => 'Help - Archives', 'url' => './fiscal/help/archives', 'key' => 'fiscal-help-archives', 'load_content' => true, 'ajax_url' => './fiscal/help/archives'],
                    ['title' => 'Help - Issues', 'url' => './fiscal/help/issues', 'key' => 'fiscal-help-issues', 'load_content' => true, 'ajax_url' => './fiscal/help/issues']
                ]
            ]
        ],
        'settings' => [
            [
                'title' => 'General Settings',
                'url' => './settings/general',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'key' => 'settings-general'
            ],
            [
                'title' => 'User Management',
                'url' => './users/',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
                'key' => 'users'
            ],
            [
                'title' => 'Password Hash',
                'url' => './passwordupd/',
                'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                'key' => 'password'
            ]
        ]
    ];
    
    // Add management interface if available
    if ($bo && !$isMirror && $menuType === 'main') {
        $menus['main'][] = [
            'title' => 'Management Interface',
            'url' => $bo,
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'key' => 'management',
            'badge' => 'External'
        ];
    }
    
    $menuItems = $menus[$menuType] ?? $menus['main'];
    
    ob_start();
    ?>
    <nav class="space-y-1" x-data="{ openMenus: {} }">
        <?php foreach ($menuItems as $index => $item): ?>
            <?php $isActive = $currentPage === $item['key']; ?>
            <?php $hasSubmenu = isset($item['submenu']) && !empty($item['submenu']); ?>
            
            <?php if ($hasSubmenu): ?>
                <div class="space-y-1">
                    <button @click="openMenus['<?= $index ?>'] = !openMenus['<?= $index ?>']"
                            class="group w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-150 <?= $isActive ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' ?>">
                        <div class="flex items-center">
                            <svg class="mr-3 w-5 h-5 flex-shrink-0 <?= $isActive ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' ?>" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/>
                            </svg>
                            <span><?= htmlspecialchars($item['title']) ?></span>
                            <?php if (isset($item['badge'])): ?>
                                <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-200 rounded-full">
                                    <?= htmlspecialchars($item['badge']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-90': openMenus['<?= $index ?>'] }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    
                    <div x-show="openMenus['<?= $index ?>']" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="pl-11 space-y-1">
                        <?php foreach ($item['submenu'] as $subItem): ?>
                            <?php $isSubActive = $currentPage === $subItem['key']; ?>
                            <?php if (isset($subItem['load_content']) && $subItem['load_content']): ?>
                                <button @click="loadContent('<?= htmlspecialchars($subItem['ajax_url'] ?? $subItem['url']) ?>', '<?= htmlspecialchars($subItem['title']) ?>', '<?= htmlspecialchars($subItem['key']) ?>')"
                                        class="group flex items-center w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-150 text-left <?= $isSubActive ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border-l-2 border-primary-600 pl-2' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' ?>">
                                    <?= htmlspecialchars($subItem['title']) ?>
                                </button>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($subItem['url']) ?>" 
                                   class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-150 <?= $isSubActive ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border-l-2 border-primary-600 pl-2' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' ?>">
                                    <?= htmlspecialchars($subItem['title']) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= htmlspecialchars($item['url']) ?>" 
                   class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-150 <?= $isActive ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border-r-2 border-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' ?>">
                    <svg class="mr-3 w-5 h-5 flex-shrink-0 <?= $isActive ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' ?>" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/>
                    </svg>
                    <span><?= htmlspecialchars($item['title']) ?></span>
                    <?php if (isset($item['badge'])): ?>
                        <span class="ml-auto px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-200 rounded-full">
                            <?= htmlspecialchars($item['badge']) ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php
    return ob_get_clean();
}

// Breadcrumb component
function renderBreadcrumb($items = []) {
    if (empty($items)) return '';
    
    ob_start();
    ?>
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <?php foreach ($items as $index => $item): ?>
                <li class="inline-flex items-center">
                    <?php if ($index > 0): ?>
                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    <?php endif; ?>
                    
                    <?php if ($index === count($items) - 1): ?>
                        <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">
                            <?= htmlspecialchars($item['title']) ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($item['url']) ?>" 
                           class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 text-sm font-medium transition-colors">
                            <?= htmlspecialchars($item['title']) ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
    return ob_get_clean();
}
?>
