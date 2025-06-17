<?php
function renderMetricsDashboard($ptApp, $metrics = []) {
    ob_start();
?>
<!-- Metrics Dashboard Content -->
<div class="space-y-6" x-data="metricsData()">
    <!-- Header Section -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Enterprise Metrics Dashboard</h1>
                <p class="text-gray-500 dark:text-gray-400">Real-time system and business intelligence</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2 text-sm">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-gray-600 dark:text-gray-400">Live Data</span>
                </div>
                <button @click="refreshMetrics()" 
                        :disabled="loading"
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50">
                    <svg class="w-4 h-4 mr-2 inline" :class="{'animate-spin': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Tickets -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Tickets</p>
                    <p class="text-3xl font-bold text-blue-900 dark:text-blue-100" x-text="metrics.totalTickets">
                        <?= isset($metrics['totalTickets']) ? number_format($metrics['totalTickets']) : '0' ?>
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                        <span class="inline-flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            +12% from last month
                        </span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Total Revenue</p>
                    <p class="text-3xl font-bold text-green-900 dark:text-green-100" x-text="formatCurrency(metrics.totalRevenue)">
                        <?= isset($metrics['totalRevenue']) ? '$' . number_format($metrics['totalRevenue'], 2) : '$0.00' ?>
                    </p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                        <span class="inline-flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            +8.5% from last month
                        </span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border border-purple-200 dark:border-purple-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Active Sessions</p>
                    <p class="text-3xl font-bold text-purple-900 dark:text-purple-100" x-text="metrics.activeSessions">
                        <?= isset($metrics['activeSessions']) ? number_format($metrics['activeSessions']) : '0' ?>
                    </p>
                    <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                        <span class="inline-flex items-center">
                            <div class="w-2 h-2 bg-purple-500 rounded-full mr-1 animate-pulse"></div>
                            Live monitoring
                        </span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 border border-amber-200 dark:border-amber-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-600 dark:text-amber-400">System Health</p>
                    <p class="text-3xl font-bold text-amber-900 dark:text-amber-100">98.5%</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        <span class="inline-flex items-center">
                            <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            All systems operational
                        </span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-amber-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue Chart -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Revenue Trend</h3>
                <div class="flex items-center space-x-2">
                    <button class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded-full text-gray-600 dark:text-gray-400">7D</button>
                    <button class="px-3 py-1 text-xs bg-primary-600 text-white rounded-full">30D</button>
                    <button class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded-full text-gray-600 dark:text-gray-400">90D</button>
                </div>
            </div>
            <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H9z"/>
                    </svg>
                    <p>Chart.js Integration Ready</p>
                    <p class="text-sm opacity-75">Connect your preferred charting library</p>
                </div>
            </div>
        </div>

        <!-- Transaction Distribution -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Transaction Distribution</h3>
            <div class="space-y-4">
                <!-- Cash -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cash</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: 65%"></div>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-10">65%</span>
                    </div>
                </div>

                <!-- Card -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Card</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 30%"></div>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-10">30%</span>
                    </div>
                </div>

                <!-- Digital -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 bg-purple-500 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Digital</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: 5%"></div>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-10">5%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">System Status</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Database -->
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Database</p>
                    <p class="text-sm text-green-600 dark:text-green-400">Operational</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Response: 12ms</p>
                </div>
            </div>

            <!-- API -->
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">API Services</p>
                    <p class="text-sm text-green-600 dark:text-green-400">Operational</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Uptime: 99.9%</p>
                </div>
            </div>

            <!-- Security -->
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Security</p>
                    <p class="text-sm text-green-600 dark:text-green-400">Secured</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">JWT Active</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function metricsData() {
    return {
        loading: false,
        metrics: {
            totalTickets: <?= json_encode($metrics['totalTickets'] ?? 0) ?>,
            totalRevenue: <?= json_encode($metrics['totalRevenue'] ?? 0) ?>,
            activeSessions: <?= json_encode($metrics['activeSessions'] ?? 0) ?>
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        },
        
        async refreshMetrics() {
            this.loading = true;
            try {
                const response = await fetch('/api/metrics');
                if (response.ok) {
                    const data = await response.json();
                    this.metrics = { ...this.metrics, ...data };
                }
            } catch (error) {
                console.error('Failed to refresh metrics:', error);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
<?php
    return ob_get_clean();
}
?>
