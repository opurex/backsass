<?php
function renderSidebar($ptApp, $currentPage = '') {
    $bo = $ptApp->getDefaultBackOfficeUrl();
    $isMirror = $ptApp->isFiscalMirror();

    $menuItems = [
        [
            'title' => 'Dashboard',
            'url' => './',
            'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z',
            'key' => 'dashboard'
        ],
        [
            'title' => 'Fiscal Records',
            'url' => './fiscal/',
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'key' => 'fiscal',
            'submenu' => [
                ['title' => 'Dashboard', 'url' => './fiscal/', 'key' => 'fiscal-dashboard'],
                ['title' => 'Export Records', 'url' => './fiscal/export', 'key' => 'fiscal-export'],
                ['title' => 'Help - Tickets', 'url' => './fiscal/help/tickets', 'key' => 'fiscal-help-tickets'],
                ['title' => 'Help - Archives', 'url' => './fiscal/help/archives', 'key' => 'fiscal-help-archives'],
                ['title' => 'Help - Issues', 'url' => './fiscal/help/issues', 'key' => 'fiscal-help-issues']
            ]
        ],
        [
            'title' => 'Password Hash',
            'url' => './passwordupd/',
            'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
            'key' => 'password'
        ]
    ];

    if ($bo && !$isMirror) {
        $menuItems[] = [
            'title' => 'Management Interface',
            'url' => $bo,
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'key' => 'management'
        ];
    }

    ob_start();
    ?>
    <!-- Sidebar -->
    <aside x-data="{
        sidebarOpen: false,
        loading: false,
        activeContentKey: '',
        init() {
            this.$watch('sidebarOpen', value => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = 'auto';
                }
            });

            // Listen for toggle events from navbar
            this.$watch('$store.sidebar.open', value => {
                this.sidebarOpen = value;
            });
        },

        loadContent(url, title, key) {
            this.loading = true;
            this.activeContentKey = key;

            // Dispatch event to main content area
            this.$dispatch('load-content', {
                url: url,
                title: title,
                key: key
            });

            // Close sidebar on mobile after selection
            if (window.innerWidth < 1024) {
                this.sidebarOpen = false;
            }
        }
    }"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform -translate-x-full lg:translate-x-0 lg:static lg:inset-0 transition-transform duration-200 ease-in-out shadow-lg lg:shadow-none"
           :class="{ 'translate-x-0': sidebarOpen }"
           @toggle-sidebar.window="sidebarOpen = !sidebarOpen">

        <!-- Sidebar Header -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-900 dark:text-white">Opurex POS</span>
            </div>

            <!-- Mobile close button -->
            <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <div class="mt-6 px-3">
            <?php
            require_once __DIR__ . '/menu.php';
            echo renderMenu($ptApp, $currentPage, 'main');
            ?>
			<!-- Dashboard Link -->
                    <a href="/fiscal/metrics"
                       class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors duration-200">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H9z"></path>
                        </svg>
                        Metrics Dashboard
                    </a>

                    <!-- Security Audit -->
                    <a href="/audit"
                       class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors duration-200">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Security Audit
                    </a>

                    <!-- Fiscal Management Dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors duration-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Fiscal Records
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="ml-6 space-y-1">

                            <?php if (isset($fiscal_sequences) && !empty($fiscal_sequences)): ?>
                                <?php foreach ($fiscal_sequences as $sequence): ?>
                                    <div x-data="{ subOpen: false }" class="relative">
                                        <button @click="subOpen = !subOpen"
                                                class="w-full flex items-center justify-between px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-800 dark:hover:text-white transition-colors duration-200">
                                            <span>Sequence <?= htmlspecialchars($sequence) ?></span>
                                            <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': subOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        <div x-show="subOpen" x-transition class="ml-4 space-y-1">
                                            <a href="/fiscal/sequence/<?= urlencode($sequence) ?>/z/"
                                               class="block px-4 py-2 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-white transition-colors duration-200">
                                                Z Tickets
                                            </a>
                                            <a href="/fiscal/sequence/<?= urlencode($sequence) ?>/tickets/"
                                               class="block px-4 py-2 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-white transition-colors duration-200">
                                                Regular Tickets
                                            </a>
                                            <a href="/fiscal/sequence/<?= urlencode($sequence) ?>/other/"
                                               class="block px-4 py-2 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-white transition-colors duration-200">
                                                Other Types
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">No sequences available</p>
                            <?php endif; ?>

                            <a href="/fiscal/export"
                               class="block px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-800 dark:hover:text-white transition-colors duration-200">
                                Export Data
                            </a>
                        </div>
                    </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>© <?= date('Y') ?> Opurex POS</span>
                <span class="text-xs">v8.9</span>
            </div>
        </div>
    </aside>

    <!-- Mobile sidebar overlay -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
         style="display: none;"></div>
    <?php
    return ob_get_clean();
}
?>