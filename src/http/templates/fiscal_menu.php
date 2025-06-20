<?php
namespace Pasteque\Server;

function renderFiscalMenu($ptApp) {
    $ret = '
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Fiscal Records Management</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tickets List -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tickets List</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">View and manage fiscal tickets by sequence</p>

                <div class="space-y-2">
                    <a href="/fiscal/sequence/1/tickets?type=ticket" 
                       class="block w-full text-left px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Sales Tickets - Sequence 1</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">View sales transaction records</span>
                    </a>

                    <a href="/fiscal/sequence/2/tickets?type=ticket" 
                       class="block w-full text-left px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Sales Tickets - Sequence 2</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">View sales transaction records</span>
                    </a>
                </div>
            </div>

            <!-- Z Tickets -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Z Tickets</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">View daily closure reports and Z tickets</p>

                <div class="space-y-2">
                    <a href="/fiscal/sequence/1/z/" 
                       class="block w-full text-left px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Z Tickets - Sequence 1</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Daily closure reports</span>
                    </a>

                    <a href="/fiscal/sequence/2/z/" 
                       class="block w-full text-left px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Z Tickets - Sequence 2</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Daily closure reports</span>
                    </a>
                </div>
            </div>

            <!-- Export Functions -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Export & Archives</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Export fiscal data and manage archives</p>

                <div class="space-y-2">
                    <a href="/fiscal/export" 
                       class="block w-full text-left px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Export Fiscal Data</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Download fiscal records</span>
                    </a>

                    <a href="/fiscal/archives" 
                       class="block w-full text-left px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">View Archives</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Browse archived data</span>
                    </a>
                </div>
            </div>

            <!-- Import Functions -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Import Data</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Import fiscal tickets and data</p>

                <div class="space-y-2">
                    <a href="/fiscal/import" 
                       class="block w-full text-left px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Import Fiscal Tickets</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Upload fiscal data files</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Information -->
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm text-blue-800 dark:text-blue-200">
                    Fiscal compliance system active. All transactions are being recorded for legal compliance.
                </span>
            </div>
        </div>
    </div>';

    return $ret;
}
?>