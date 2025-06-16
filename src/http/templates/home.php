<?php

function render($ptApp, $data) {
    $bo = $ptApp->getDefaultBackOfficeUrl();
    $isMirror = $ptApp->isFiscalMirror();

    ob_start(); // Start output buffering

    include 'header.html'; // contains <html>...<body>

    ?>
    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 p-4 min-h-screen hidden md:block">
      <h2 class="text-xl font-bold mb-6">Opurex POS Server Admin</h2>
      <nav class="space-y-4">
        <a href="./fiscal/" class="block text-blue-600 hover:underline">Fiscal Records</a>
        <?php if ($bo && !$isMirror): ?>
          <a href="<?= htmlspecialchars($bo) ?>" class="block text-blue-600 hover:underline">Management Interface</a>
        <?php endif; ?>
//         <a href="./passwordupd/" class="block text-blue-600 hover:underline">Password Hash</a>
<a href="/passwordupd/" class="block text-blue-600 hover:underline">Password Hash</a>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
      <!-- Navbar -->
      <header class="bg-white dark:bg-gray-800 shadow p-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Welcome to Opurex POS Server API</h1>
        <button onclick="document.documentElement.classList.toggle('dark')" class="px-4 py-2 rounded bg-primary text-white hover:bg-indigo-600">
          Toggle Dark Mode
        </button>
      </header>

      <!-- Page Content -->
      <main class="p-6">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6 max-w-2xl mx-auto">
          <h2 class="text-xl font-semibold mb-4 text-center">Access Options</h2>
          <ul class="space-y-4 text-center">
            <li><a href="./fiscal/" class="text-blue-600 hover:underline">View your fiscal records and archives</a></li>
            <?php if ($bo && !$isMirror): ?>
              <li><a href="<?= htmlspecialchars($bo) ?>" class="text-blue-600 hover:underline">Access the suggested management interface</a></li>
            <?php endif; ?>
            <li><a href="./passwordupd/" class="text-blue-600 hover:underline">Generate a password hash</a></li>
          </ul>
          <p class="text-sm text-center text-gray-400 mt-6">Powered by OpurexPOS</p>
        </div>
      </main>
    </div>
    <?php

    include 'footer.html'; // contains </body></html>

    return ob_get_clean(); // Return full page content
}
