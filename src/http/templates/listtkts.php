<?php

use \Pasteque\Server\System\DateUtils;


function renderTicket($tkt) {
    $data = json_decode($tkt['content']);
    $output = ($data !== null) ? json_encode($data, JSON_PRETTY_PRINT) : $tkt['content'];

    return '
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4">
        <h3 class="text-lg font-semibold text-primary mb-2">Ticket #' . htmlspecialchars($tkt['number']) . '</h3>
        <p class="text-sm text-gray-500 mb-1">Signature: <span class="font-mono">' . htmlspecialchars($tkt['signature_status']) . '</span></p>
        <p class="text-xs text-gray-400 break-words">Sig: ' . htmlspecialchars($tkt['signature']) . '</p>
        <p class="text-sm text-gray-500 mb-3">Saved at: ' . DateUtils::readDate($tkt['date'])->format('d/m/Y H:i:s') . '</p>
        <pre class="text-xs bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded p-2 overflow-x-auto">' . htmlspecialchars($output) . '</pre>
    </div>';
}

function renderPagination($page, $pageCount) {
    if ($pageCount <= 1) return '';

    $ret = '<div class="flex justify-center mt-8 space-x-2">';
    for ($i = 0; $i < $pageCount; $i++) {
        $ret .= '<a href="?page=' . $i . '" class="px-3 py-1 rounded ' .
            ($i == $page
                ? 'bg-primary text-white'
                : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600') .
            '">' . $i . '</a>';
    }
    $ret .= '</div>';
    return $ret;
}

function render($ptApp, $data) {
    $page = isset($data['page']) ? (int)$data['page'] : 0;
    $pageCount = isset($data['pageCount']) ? (int)$data['pageCount'] : 1;
    $tickets = $data['tickets'];

    $ret = '<h2 class="text-2xl font-bold mb-6 text-center">List of ' . htmlspecialchars($data['typeName']) . '</h2>';

    if (count($tickets) === 0) {
        return '<p class="text-gray-500 text-center">No records found.</p>';
    }

    // Render top pagination
    $ret .= renderPagination($page, $pageCount);

    // Render 3 cards per row
    $ret .= '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">';
    foreach ($tickets as $tkt) {
        $ret .= renderTicket($tkt);
    }
    $ret .= '</div>';

    // Render bottom pagination
    $ret .= renderPagination($page, $pageCount);

    return $ret;
}

function renderFiscalDashboardContent($ptApp) {
    ob_start();
    ?>
    <div class="space-y-6">
        <!-- Fiscal Dashboard Header -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Fiscal Records Dashboard</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Manage fiscal tickets and compliance records</p>
                </div>
                <div class="flex space-x-3">
                    <button class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        Export Records
                    </button>
                    <button class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Generate Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Fiscal Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Tickets</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">2,847</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Today's Records</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">156</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Issues</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">3</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Fiscal Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Fiscal Activity</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ticket ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#FT-001234</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Sale</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">$145.00</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200 rounded-full">Completed</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">2 minutes ago</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#FT-001233</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Return</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">-$25.00</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200 rounded-full">Processing</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">1 hour ago</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}