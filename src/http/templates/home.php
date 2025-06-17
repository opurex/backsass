<?php

function renderHome($ptApp, $data) {
    $bo = $ptApp->getDefaultBackOfficeUrl();
    $isMirror = $ptApp->isFiscalMirror();

//    ob_start();

    include 'header.html';
    include 'sidebar.php';
    include 'navbar.php';
    ?>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="dashboardManager()" @load-content.window="handleContentLoad($event.detail)">
        <?= renderSidebar($ptApp, 'dashboard') ?>

        <div class="lg:pl-64">
            <?= renderNavbar(null, 'Dashboard') ?>

            <!-- Main Content -->
            <main class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                    <!-- Dynamic Content Area -->
                    <div x-show="activeContent.key"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-4"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="bg-white dark:bg-gray-800 rounded-xl shadow-card mb-8"
                         style="display: none;">

                        <!-- Content Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center space-x-3">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white" x-text="activeContent.title"></h2>
                                <div x-show="loading" class="w-5 h-5">
                                    <svg class="animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <button @click="closeContent()"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Content Body -->
                        <div class="p-6">
                            <div x-show="loading" class="flex items-center justify-center py-12">
                                <div class="text-center">
                                    <svg class="animate-spin mx-auto h-8 w-8 text-primary-600 mb-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400">Loading content...</p>
                                </div>
                            </div>

                            <div x-show="!loading && contentHtml" x-html="contentHtml" class="prose prose-gray dark:prose-invert max-w-none"></div>

                            <div x-show="!loading && error" class="text-center text-red-600 dark:text-red-400">
                                <p x-text="error"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Default Dashboard Content -->
                    <div x-show="!activeContent.key">
                        <!-- Welcome Section -->
                        <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl shadow-sm p-6 mb-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-2xl font-bold text-white mb-2">Welcome to Opurex POS Server</h2>
                                    <p class="text-primary-100">Manage your point-of-sale operations with ease</p>
                                </div>
                                <div class="hidden sm:block">
                                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card p-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">API Version</p>
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">v8.9</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card p-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Server Status</p>
                                        <p class="text-2xl font-semibold text-green-600 dark:text-green-400">Active</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card p-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/20 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Service Status</p>
                                        <p class="text-2xl font-semibold text-primary-600 dark:text-primary-400">Ready</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php include 'footer.html'; ?>
        </div>
    </div>

    <script>
    function dashboardManager() {
        return {
            activeContent: { key: '', title: '', url: '' },
            contentHtml: '',
            loading: false,
            error: '',

            handleContentLoad(detail) {
                this.loading = true;
                this.error = '';
                this.activeContent = detail;

                // Simulate content loading (replace with actual AJAX call)
                setTimeout(() => {
                    // This would be replaced with actual content loading logic
                    this.contentHtml = `<h2 class="text-xl font-semibold mb-4">${detail.title}</h2><p>Content for ${detail.title} would be loaded here.</p>`;
                    this.loading = false;
                }, 500);
            },

            closeContent() {
                this.activeContent = { key: '', title: '', url: '' };
                this.contentHtml = '';
                this.error = '';
            }
        }
    }

    // Alpine.js initialization
    document.addEventListener('alpine:init', () => {
        Alpine.store('sidebar', {
            open: false,
            toggle() {
                this.open = !this.open;
            }
        });
    });
</script>
    <?php

//    $html = ob_get_clean();
//    $response->getBody()->write($html);
//    return $response;
}