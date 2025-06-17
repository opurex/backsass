<?php

function render($ptApp, $data) {
    ob_start();

    include 'header.html';
    include 'sidebar.php';
    include 'navbar.php';
    ?>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <?= renderSidebar($ptApp, 'password') ?>

        <div class="lg:pl-64">
            <?= renderNavbar(null, 'Password Hash Generator') ?>

            <!-- Main Content -->
            <main class="py-6">
                <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card p-8">
                        <!-- Header -->
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                Generate Password Hash
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400">
                                Create secure password hashes for user authentication
                            </p>
                        </div>

                        <?php if (isset($data['error'])): ?>
                        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-red-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm text-red-800 dark:text-red-400">
                                    <?= htmlspecialchars($data['error']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($data['hash'])): ?>
                        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-green-800 dark:text-green-400 mb-2">
                                        Password hash generated successfully!
                                    </div>
                                    <div class="bg-white dark:bg-gray-900 border border-green-200 dark:border-green-700 rounded-lg p-3">
                                        <code class="text-sm text-gray-900 dark:text-white break-all font-mono">
                                            <?= htmlspecialchars($data['hash']) ?>
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <form action="." method="post" class="space-y-6">
                            <div>
                                <label for="pwd1" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Password
                                </label>
                                <input type="password" 
                                       name="password1" 
                                       id="pwd1" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors" 
                                       placeholder="Enter your password" />
                            </div>

                            <div>
                                <label for="pwd2" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Confirm Password
                                </label>
                                <input type="password" 
                                       name="password2" 
                                       id="pwd2" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors" 
                                       placeholder="Confirm your password" />
                            </div>

                            <div class="pt-4">
                                <button type="submit" 
                                        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                    Generate Hash
                                </button>
                            </div>
                        </form>

                        <!-- Help Text -->
                        <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Security Notes:</h3>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• Passwords are hashed using secure algorithms</li>
                                <li>• Original passwords are never stored</li>
                                <li>• Use strong passwords with mixed characters</li>
                                <li>• Keep your password hash secure</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php
    include 'footer.html';
    return ob_get_clean();
}
?>