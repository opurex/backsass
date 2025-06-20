<?php

function renderDefaultHome($ptApp) {
    $ret = '
    <!-- Main Dashboard Layout -->
    <div x-data="{
        sidebarOpen: false,
        darkMode: false,
        activePage: \'dashboard\',
        activeContent: { key: null, title: \'\', url: \'\' },
        loading: false,
        error: null,
        contentHtml: \'\',
        openMenus: {},
        
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
        
        toggleSubmenu(menu) {
            this.openMenus[menu] = !this.openMenus[menu];
        },
        
        loadContent(content) {
            if (this.loading) return;
            
            this.loading = true;
            this.error = null;
            this.activeContent = content;
            
            if (content.url) {
                fetch(content.url)
                    .then(response => {
                        if (!response.ok) throw new Error(\'Failed to load content\');
                        return response.text();
                    })
                    .then(html => {
                        this.contentHtml = html;
                        this.loading = false;
                    })
                    .catch(err => {
                        this.error = \'Error loading content: \' + err.message;
                        this.loading = false;
                    });
            } else {
                this.loading = false;
            }
        },
        
        handleContentLoad(detail) {
            this.loadContent(detail);
        }
    }" 
    @load-content.window="handleContentLoad($event.detail)"
    @toggle-sidebar.window="toggleSidebar()"
    class="min-h-screen flex bg-gray-50 dark:bg-gray-900">
    
        <!-- Sidebar -->
        <div x-cloak 
             :class="{ \'translate-x-0\': sidebarOpen, \'-translate-x-full\': !sidebarOpen }" 
             class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg transform lg:translate-x-0 lg:static lg:inset-0 sidebar-transition">
            
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-16 px-6 bg-primary-600 text-white">
                <div class="flex items-center space-x-2">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2L3 7v11a1 1 0 001 1h12a1 1 0 001-1V7l-7-5z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xl font-bold">Opurex POS</span>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="mt-8 px-4 space-y-2">
                <!-- Dashboard Cards -->
                <div class="space-y-3">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Fiscal Records</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Manage fiscal data and exports</p>
                                <button @click="loadContent({ key: \'fiscal\', title: \'Fiscal Records\', url: \'/fiscal\' })" 
                                        class="mt-2 text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium">
                                    Access →
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Metrics Dashboard</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">View system metrics</p>
                                <button @click="loadContent({ key: \'metrics\', title: \'Metrics Dashboard\', url: \'/fiscal/metrics\' })" 
                                        class="mt-2 text-green-600 dark:text-green-400 hover:underline text-sm font-medium">
                                    View →
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                                <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Security Audit</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Review security logs</p>
                                <button @click="loadContent({ key: \'security\', title: \'Security Audit\', url: \'/fiscal/audit\' })" 
                                        class="mt-2 text-yellow-600 dark:text-yellow-400 hover:underline text-sm font-medium">
                                    Review →
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Password Hash</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Generate password hashes</p>
                                <button @click="loadContent({ key: \'password\', title: \'Password Hash\', url: \'/fiscal/password\' })" 
                                        class="mt-2 text-purple-600 dark:text-purple-400 hover:underline text-sm font-medium">
                                    Generate →
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
        
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
             style="display: none;">
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 lg:ml-0">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between h-16 px-6">
                    <div class="flex items-center space-x-4">
                        <button @click="sidebarOpen = true" class="lg:hidden">
                            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" 
                                   placeholder="Search transactions, customers..." 
                                   class="w-64 px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        
                        <button @click="darkMode = !darkMode" 
                                class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            <svg x-show="!darkMode" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <svg x-show="darkMode" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </button>
                        
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold">A</span>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Admin</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Administrator</div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Dynamic Content Area -->
            <main class="p-6">
                <!-- Content with transition -->
                <div x-show="activeContent.key" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4" x-text="activeContent.title"></h2>
                    
                    <!-- Loading state -->
                    <div x-show="loading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                        <span class="ml-2 text-gray-600 dark:text-gray-400">Loading...</span>
                    </div>
                    
                    <!-- Content -->
                    <div x-show="!loading && contentHtml" x-html="contentHtml"></div>
                    
                    <!-- Error state -->
                    <div x-show="!loading && error" class="text-red-600 dark:text-red-400 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <p x-text="error"></p>
                    </div>
                </div>
                
                <!-- Welcome message when no content is selected -->
                <div x-show="!activeContent.key" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v1m0 0h6m-6 0V6a2 2 0 016 0v1m0 0v5h6m-6 0v4a2 2 0 01-4 4H9a2 2 0 01-4-4v-4m6 0a2 2 0 00-2-2h-4a2 2 0 00-2 2v4a2 2 0 002 2h4z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Welcome to Opurex POS</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Manage your point of sale system efficiently</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">Select an option from the sidebar to get started</p>
                </div>
            </main>
        </div>
    </div>';
    
    return $ret;
}
?>
