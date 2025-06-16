<?php

function render($ptApp, $data) {
    ob_start();
    include 'header.html'; // Already includes <body> and Tailwind
    ?>

    <div class="flex flex-1 items-center justify-center min-h-screen p-4 bg-gray-100 dark:bg-darkbg">
        <div class="w-full max-w-md bg-white dark:bg-gray-800 shadow-md rounded-lg p-8">
            <h1 class="text-2xl font-bold text-center text-gray-800 dark:text-white mb-6">
                Opurex POS<br><span class="text-sm font-normal">Login</span>
            </h1>
            <form name="loginform" method="post" action="." class="space-y-4">
                <div>
                    <label for="user_login" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                    <input type="text" name="user" id="user_login" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:ring-primary focus:border-primary" required>
                </div>
                <div>
                    <label for="user_pass" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input type="password" name="password" id="user_pass" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:ring-primary focus:border-primary" required>
                </div>
                <div>
                    <input type="submit" name="submit" id="submit" value="Log In" class="w-full py-2 px-4 bg-primary text-white rounded-md hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                </div>
            </form>
        </div>
    </div>

    <?php
    include 'footer.html';
    return ob_get_clean();
}
