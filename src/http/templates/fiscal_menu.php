<?php
function renderFiscalMenu($ptApp, $data = [], $currentPage = '') {
    $sequences = $data['sequences'] ?? [];
    $types = $data['types'] ?? [];
    $archives = $data['archives'] ?? [];
    $user = $data['user'] ?? '';
    
    ob_start();
    ?>
    <div class="space-y-6">
        <!-- Fiscal Dashboard -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Fiscal Management
            </h3>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <a href="./fiscal/export" class="flex items-center p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/40 transition-colors">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-primary-700 dark:text-primary-300 font-medium">Export Records</span>
                </a>
                
                <a href="./fiscal/disconnect" class="flex items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="text-red-700 dark:text-red-300 font-medium">Disconnect</span>
                </a>
            </div>
        </div>

        <?php if (!empty($sequences)): ?>
        <!-- Sequences Navigation -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card p-6">
            <h4 class="text-md font-semibold mb-4 text-gray-900 dark:text-white">Fiscal Sequences</h4>
            <div class="space-y-3">
                <?php foreach ($sequences as $sequence): ?>
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h5 class="font-medium text-gray-900 dark:text-white mb-3">Sequence #<?= htmlspecialchars($sequence) ?></h5>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <a href="./fiscal/sequence/<?= urlencode($sequence) ?>/z/" 
                           class="flex items-center px-3 py-2 text-sm bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Z Tickets
                        </a>
                        
                        <a href="./fiscal/sequence/<?= urlencode($sequence) ?>/tickets/" 
                           class="flex items-center px-3 py-2 text-sm bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            Regular Tickets
                        </a>
                        
                        <?php if (!empty($types)): ?>
                            <?php foreach ($types as $type): ?>
                            <a href="./fiscal/sequence/<?= urlencode($sequence) ?>/other?type=<?= urlencode($type) ?>" 
                               class="flex items-center px-3 py-2 text-sm bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 rounded hover:bg-yellow-100 dark:hover:bg-yellow-900/40 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <?= ucfirst(htmlspecialchars($type)) ?>s
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($data['gpg']) && $data['gpg'] && !empty($archives)): ?>
        <!-- Archives Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card p-6">
            <h4 class="text-md font-semibold mb-4 text-gray-900 dark:text-white">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
                </svg>
                Archives
            </h4>
            <div class="space-y-2">
                <?php foreach ($archives as $archive): ?>
                <a href="./fiscal/archive/<?= urlencode($archive->getNumber()) ?>" 
                   class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <span class="text-gray-900 dark:text-white">Archive #<?= htmlspecialchars($archive->getNumber()) ?></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Archive Creation Form -->
            <form method="POST" action="./fiscal/createarchive" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h5 class="font-medium mb-3 text-gray-900 dark:text-white">Create New Archive</h5>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="date" name="dateStart" required 
                           class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <input type="date" name="dateStop" required 
                           class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                <button type="submit" 
                        class="mt-3 w-full px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                    Create Archive
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Help Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card p-6">
            <h4 class="text-md font-semibold mb-4 text-gray-900 dark:text-white">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Help & Documentation
            </h4>
            <div class="space-y-2">
                <a href="./fiscal/help/tickets" class="flex items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    <span class="text-blue-700 dark:text-blue-300">Tickets Help</span>
                </a>
                
                <a href="./fiscal/help/archives" class="flex items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-green-700 dark:text-green-300">Archives Help</span>
                </a>
                
                <a href="./fiscal/help/issues" class="flex items-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/40 transition-colors">
                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="text-yellow-700 dark:text-yellow-300">Known Issues</span>
                </a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
