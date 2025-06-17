<?php
function renderBreadcrumbs($items = []) {
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

function renderQuickActions($actions = []) {
    if (empty($actions)) return '';
    
    ob_start();
    ?>
    <div class="flex flex-wrap gap-2 mb-6">
        <?php foreach ($actions as $action): ?>
            <button type="button" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    <?= isset($action['onclick']) ? 'onclick="' . htmlspecialchars($action['onclick']) . '"' : '' ?>
                    <?= isset($action['href']) ? 'onclick="window.location.href=\'' . htmlspecialchars($action['href']) . '\'"' : '' ?>>
                <?php if (isset($action['icon'])): ?>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $action['icon'] ?>"/>
                    </svg>
                <?php endif; ?>
                <?= htmlspecialchars($action['title']) ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

function renderDataTable($headers = [], $data = [], $options = []) {
    ob_start();
    ?>
    <div class="bg-white dark:bg-gray-800 shadow-card rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= htmlspecialchars($header) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($data as $row): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <?php foreach ($row as $cell): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= is_string($cell) ? htmlspecialchars($cell) : $cell ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($data)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No data available</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding some records.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>
