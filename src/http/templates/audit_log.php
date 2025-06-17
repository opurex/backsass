<?php
function renderAuditLog($ptApp, $logs = []) {
    ob_start();
?>
<!-- Audit Log Dashboard -->
<div class="space-y-6" x-data="auditLogData()">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Security Audit Log</h1>
                <p class="text-gray-500 dark:text-gray-400">Track all system activities and security events</p>
            </div>
            <div class="flex items-center space-x-3">
                <select class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm">
                    <option>Last 24 hours</option>
                    <option>Last 7 days</option>
                    <option>Last 30 days</option>
                    <option>Custom range</option>
                </select>
                <button class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Export Log
                </button>
            </div>
        </div>
    </div>

    <!-- Security Alerts -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-red-900 dark:text-red-100">3 Failed Logins</p>
                    <p class="text-sm text-red-600 dark:text-red-400">In the last hour</p>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-yellow-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-yellow-900 dark:text-yellow-100">12 Warnings</p>
                    <p class="text-sm text-yellow-600 dark:text-yellow-400">System alerts</p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-green-900 dark:text-green-100">98.5% Uptime</p>
                    <p class="text-sm text-green-600 dark:text-green-400">This month</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Entries -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
                <div class="flex items-center space-x-2">
                    <input type="search" placeholder="Search logs..." 
                           class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm w-64 focus:ring-2 focus:ring-primary-500">
                    <button class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Sample Log Entries -->
            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <div class="flex items-start space-x-4">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mt-1">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Successful Login</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">2 minutes ago</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">User admin logged in from IP 192.168.1.100</p>
                        <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>Session ID: sess_1234567890</span>
                            <span>User Agent: Chrome/120.0.0.0</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <div class="flex items-start space-x-4">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center mt-1">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Fiscal Ticket Created</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">5 minutes ago</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">New fiscal ticket #FT-2024-001234 created by admin</p>
                        <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>Amount: $125.50</span>
                            <span>Sequence: 0001</span>
                            <span>Register: POS-001</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <div class="flex items-start space-x-4">
                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900/20 rounded-full flex items-center justify-center mt-1">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Failed Login Attempt</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">15 minutes ago</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Failed login attempt for user: invalid_user from IP 192.168.1.200</p>
                        <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="text-red-600 dark:text-red-400">Reason: Invalid credentials</span>
                            <span>Attempts: 3/5</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Load More -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-center">
            <button class="px-4 py-2 text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium">
                Load More Entries
            </button>
        </div>
    </div>
</div>

<script>
function auditLogData() {
    return {
        searchQuery: '',
        selectedTimeframe: '24h',
        
        filterLogs() {
            // Implementation for filtering logs
        },
        
        exportLogs() {
            // Implementation for exporting logs
        }
    }
}
</script>
<?php
    return ob_get_clean();
}
?>
