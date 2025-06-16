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

