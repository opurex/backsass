<?php
function renderSidebar($ptApp, $currentPage = '') {
    $bo = $ptApp->getDefaultBackOfficeUrl();
    $isMirror = $ptApp->isFiscalMirror();

    ob_start();
    ?>
    <!-- Sidebar -->
    <aside x-data="{
        sidebarOpen: false,
        activePage: '<?= $currentPage ?>',
        openMenus: {},
        init() {
            // Listen for mobile toggle
            this.$watch('sidebarOpen', value => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = 'auto';
                }
            });

            // Listen for toggle-sidebar event
            this.$watch('$store.sidebar.open', value => {
                this.sidebarOpen = value;
            });
        },

        toggleSubmenu(menuKey) {
            this.openMenus[menuKey] = !this.openMenus[menuKey];
        },

        loadContent(url, title, key) {
            // Dispatch event to main content area
            this.$dispatch('load-content', {
                url: url,
                title: title,
                key: key
            });

            // Update active page
            this.activePage = key;

            // Close sidebar on mobile after selection
            if (window.innerWidth < 1024) {
                this.sidebarOpen = false;
            }
        }
    }"
           class="sidebar-fixed bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out overflow-y-auto"
           :class="{ 'translate-x-0': sidebarOpen }"
           @toggle-sidebar.window="sidebarOpen = !sidebarOpen"
           x-init="
               // Initialize Alpine store
               if (!$store.sidebar) {
                   Alpine.store('sidebar', {
                       open: false,
                       toggle() {
                           this.open = !this.open;
                       }
                   });
               }
           ">

        <!-- Sidebar Header -->
        <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white">Opurex POS</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Server Dashboard</p>
                </div>
            </div>

            <!-- Mobile close button -->
            <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-6 space-y-2">
            <!-- Dashboard -->
            <a href="./" 
               class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors duration-200"
               :class="activePage === 'dashboard' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border-r-2 border-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                </svg>
                Dashboard
            </a>

            <!-- Metrics Dashboard -->
            <a href="/fiscal/metrics"
               class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors duration-200"
               :class="activePage === 'metrics' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border-r-2 border-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Metrics Dashboard
            </a>

            <!-- Security Audit -->
            <a href="/audit"
               class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors duration-200"
               :class="activePage === 'audit' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border-r-2 border-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Security Audit
            </a>

            <!-- Fiscal Records Section -->
            <div class="space-y-1">
                <button @click="toggleSubmenu('fiscal')" 
                        class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-colors duration-200"
                        :class="activePage.startsWith('fiscal') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Fiscal Records
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" 
                         :class="{ 'rotate-180': openMenus.fiscal }" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Fiscal Submenu -->
                <div x-show="openMenus.fiscal" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="ml-6 space-y-1">

                    <button @click="loadContent('./fiscal/', 'Fiscal Dashboard', 'fiscal-dashboard')"
                            class="w-full text-left px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-800 dark:hover:text-white rounded-md transition-colors duration-200"
                            :class="activePage === 'fiscal-dashboard' ? 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-white' : ''">
                        Dashboard
                    </button>

                    <button @click="loadContent('./fiscal/export', 'Export Records', 'fiscal-export')"
                            class="w-full text-left px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-800 dark:hover:text-white rounded-md transition-colors duration-200"
                            :class="activePage === 'fiscal-export' ? 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-white' : ''">
                        Export Records
                    </button>

                    <button @click="loadContent('./fiscal/help/tickets', 'Help - Tickets', 'fiscal-help-tickets')"
                            class="w-full text-left px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-800 dark:hover:text-white rounded-md transition-colors duration-200"
                            :class="activePage === 'fiscal-help-tickets' ? 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-white' : ''">
                        Help - Tickets
                    </button>

                    <button @click="loadContent('./fiscal/help/archives', 'Help - Archives', 'fiscal-help-archives')"
                            class="w-full text-left px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-800 dark:hover:text-white rounded-md transition-colors duration-200"
                            :class="activePage === 'fiscal-help-archives' ? 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-white' : ''">
                        Help - Archives
                    </button>

                    <button @click="loadContent('./fiscal/help/issues', 'Help - Issues', 'fiscal-help-issues')"
                            class="w-full text-left px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-800 dark:hover:text-white rounded-md transition-colors duration-200"
                            :class="activePage === 'fiscal-help-issues' ? 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-white' : ''">
                        Help - Issues
                    </button>
                </div>
            </div>

            <!-- Password Management -->
            <a href="./passwordupd/"
               class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors duration-200"
               :class="activePage === 'password' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border-r-2 border-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Password Hash
            </a>

            <?php if ($bo && !$isMirror): ?>
            <!-- Management Interface -->
            <a href="<?= htmlspecialchars($bo) ?>"
               class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors duration-200"
               :class="activePage === 'management' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border-r-2 border-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                </svg>
                Management Interface
                <span class="ml-auto px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200 rounded-full">
                    External
                </span>
            </a>
            <?php endif; ?>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>© <?= date('Y') ?> Opurex POS</span>
                <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">v8.9</span>
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
         class="fixed inset-0 z-30 bg-gray-600 bg-opacity-75 lg:hidden"
         style="display: none;"></div>
    <?php
    return ob_get_clean();
}
?>