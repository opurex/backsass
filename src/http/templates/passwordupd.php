<?php

function render($ptApp, $data) {
    ob_start();
    include 'header.html'; // Your global header with Tailwind config
    ?>

    <div class="flex flex-1 items-center justify-center min-h-screen p-4 bg-gray-100 dark:bg-darkbg">
        <div class="w-full max-w-lg bg-white dark:bg-gray-900 rounded-lg shadow p-8">
            <h1 class="text-2xl font-bold text-center text-gray-800 dark:text-white mb-6">
                Generate Password Hash
            </h1>
            <form action="." method="post" class="space-y-4">
                <div>
                    <label for="pwd1" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Password</label>
                    <input type="password" name="password1" id="pwd1" required
                        class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-primary focus:border-primary" />
                </div>
                <div>
                    <label for="pwd2" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Confirm Password</label>
                    <input type="password" name="password2" id="pwd2" required
                        class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-primary focus:border-primary" />
                </div>

                <?php if (!empty($data['error'])): ?>
                    <div class="text-red-500 text-sm font-semibold">
                        <?= htmlspecialchars($data['error']) ?>
                    </div>
                <?php endif; ?>

                <div>
                    <button type="submit" name="submit" class="w-full bg-primary hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded">
                        Generate Hash
                    </button>
                </div>
            </form>

            <?php if (!empty($data['hash'])): ?>
                <div class="mt-6 text-center text-sm text-gray-700 dark:text-gray-300">
                    <p>The generated password hash is:</p>
                    <p class="mt-2 font-mono break-all bg-gray-100 dark:bg-gray-800 p-2 rounded">
                        <?= htmlspecialchars($data['hash']) ?>
                    </p>
                    <p class="mt-2">You can send it to your administrator for registration.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    include 'footer.html';
    return ob_get_clean();
}
