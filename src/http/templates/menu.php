<?php

use \Pasteque\Server\Model\FiscalTicket;

function render($ptApp, $data) {
    $bo = $ptApp->getDefaultBackOfficeUrl();
    ob_start();
    ?>

<!-- Sidebar -->
<aside class="w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 p-4 min-h-screen hidden md:block">
  <h2 class="text-xl font-bold mb-6">Opurex POS Server Admin</h2>
  <nav class="space-y-4">
    <a href="./fiscal/" class="block text-blue-600 hover:underline">Fiscal Records</a>
    <?php if ($bo && !$ptApp->isFiscalMirror()) { ?>
      <a href="<?= htmlspecialchars($bo) ?>" class="block text-blue-600 hover:underline">Management Interface</a>
    <?php } ?>
    <a href="./passwordupd/" class="block text-blue-600 hover:underline">Password Hash</a>
  </nav>
</aside>

<!-- Main Content -->
<div class="flex-1 flex flex-col">
  <!-- Navbar -->
 <header class="bg-white dark:bg-gray-800 shadow px-6 py-4 flex items-center justify-between">
   <!-- Title -->
   <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
     Welcome to Opurex POS Server API
   </h1>

   <!-- Right section: Dark Mode + User Info -->
   <div class="flex items-center space-x-6">

     <!-- Toggle Dark Mode Button -->
     <button id="darkToggleBtn" aria-label="Toggle Dark Mode"
       class="text-gray-700 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
       <!-- Icon swaps in JS -->
       <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
               d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z" />
       </svg>
       <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
               d="M12 3v2m0 14v2m9-9h2M3 12H1m15.36-6.36l1.42 1.42M6.34 17.66l-1.42 1.42m0-13.42L6.34 6.34m12.02 12.02l-1.42-1.42" />
       </svg>
     </button>

     <!-- User Info -->
     <p class="text-sm text-gray-600 dark:text-gray-300">
       Connected as <?= htmlspecialchars($data['user']) ?>.
       <a href="./disconnect" class="text-blue-600 hover:underline ml-2">Logout</a>
     </p>
   </div>
 </header>


  <!-- Page Content -->
  <main class="p-6 space-y-6">
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6">

      <h2 class="text-xl font-bold mb-4">Consult Fiscal Data</h2>
      <div class="space-y-2">
        <h3 class="font-semibold">List All Tickets by Register</h3>
        <ul class="list-disc list-inside space-y-1">
          <?php foreach ($data['sequences'] as $sequence): ?>
            <li>
              <?= htmlspecialchars($sequence) ?>:
              <a href="./sequence/<?= htmlspecialchars($sequence) ?>/z/" class="text-blue-600 hover:underline" target="_blank">Z Tickets</a>
              <a href="./sequence/<?= htmlspecialchars($sequence) ?>/tickets/" class="text-blue-600 hover:underline" target="_blank">Tickets</a>
              <?php foreach ($data['types'] as $type): ?>
                <?php if ($type != FiscalTicket::TYPE_ZTICKET && $type != FiscalTicket::TYPE_TICKET): ?>
                  <a href="./sequence/<?= htmlspecialchars($sequence) ?>/other?type=<?= htmlspecialchars($type) ?>" class="text-blue-600 hover:underline" target="_blank"><?= htmlspecialchars($type) ?></a>
                <?php endif; ?>
              <?php endforeach; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <?php if (!empty($data['gpg'])): ?>
        <div class="mt-6">
          <h3 class="text-lg font-semibold">Archives</h3>
          <?php if (count($data['archives']) > 0): ?>
            <h4 class="font-medium mt-2">Available Archives</h4>
            <p class="text-sm text-gray-600">
              Archives must be retained for at least 6 years. They're signed using OpenPGP. Use a PGP tool like <a href="https://gnupg.org" class="text-blue-600 hover:underline" target="_blank">GnuPG</a> with the public key from your provider.
            </p>
            <ul class="list-disc list-inside mt-2">
              <?php foreach ($data['archives'] as $archive): ?>
                <?php $num = $archive->get('number'); if ($num == 0) continue; ?>
                <?php $info = $archive->get('info');
                      $name = $info->get('name') ?? '';
                      if (!$name && $info->get('dateStart') && $info->get('dateStop')) {
                        $start = \DateTime::createFromFormat('Y-m-d H:i:s', $info->get('dateStart'));
                        $stop = \DateTime::createFromFormat('Y-m-d H:i:s', $info->get('dateStop'));
                        $name = 'From ' . $start->format('d/m/Y H:i:s') . ' to ' . $stop->format('d/m/Y H:i:s');
                      }
                      $generated = '';
                      if ($info->get('generated')) {
                        $genDate = \DateTime::createFromFormat('Y-m-d H:i:s', $info->get('generated'));
                        $generated = 'Generated on ' . $genDate->format('d/m/Y') . ' at ' . $genDate->format('H:i:s');
                      }
                ?>
                <li><a href="./archive/<?= $num ?>" class="text-blue-600 hover:underline">Archive <?= $num ?> - <?= htmlspecialchars($name) ?></a> <?= $generated ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-sm text-gray-500">No archives generated yet.</p>
          <?php endif; ?>

          <h4 class="font-medium mt-4">Archives Being Created</h4>
          <?php if (count($data['archiverequests']) > 0): ?>
            <ul>
              <?php foreach ($data['archiverequests'] as $ar): ?>
                <li><?= $ar->getId() ?> - From <?= $ar->getStartDate()->format('d/m/Y H:i:s') ?> to <?= $ar->getStopDate()->format('d/m/Y H:i:s') ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-sm text-gray-500">No pending archive requests.</p>
          <?php endif; ?>

          <h4 class="font-medium mt-4">Create New Archive</h4>
          <form action="createarchive" method="post" class="space-y-2">
            <label class="block">
              From: <input type="date" name="dateStart" class="border text-blue-600 rounded px-2 py-1" required>
            </label>
            <label class="block">
              To: <input type="date" name="dateStop" class="border text-blue-600 rounded px-2 py-1" required>
            </label>
            <input type="submit" value="Send" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
          </form>
        </div>
      <?php endif; ?>

      <div class="mt-6">
        <h3 class="text-lg font-semibold">Export Tickets</h3>
        <ul class="list-disc list-inside">
          <li><a href="./export?period=P7D" class="text-blue-600 hover:underline">Export 1 week</a></li>
          <li><a href="./export?period=P14D" class="text-blue-600 hover:underline">Export 2 weeks</a></li>
          <li><a href="./export?period=P1M" class="text-blue-600 hover:underline">Export 1 month</a></li>
        </ul>
        <form action="export" method="get" class="mt-2">
          <label class="block">
            From: <input type="date" name="from" class="border text-blue-600 rounded px-2 py-1" required>
          </label>
          <input type="submit" value="Export" class="mt-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        </form>
        <form action="export" method="get" class="mt-2">
          <label class="block">From:
            <input type="date" name="from" class="border  text-blue-600 rounded px-2 py-1" required>
          </label>
          <label class="block">To:
            <input type="date" name="to" class="border text-blue-600 rounded px-2 py-1" required>
          </label>
          <input type="submit" value="Export" class="mt-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        </form>
      </div>

      <?php if ($ptApp->isFiscalMirror()): ?>
        <div class="mt-6">
          <h3 class="text-lg font-semibold">Import Tickets</h3>
          <form method="POST" action="import" enctype="multipart/form-data" class="space-y-2">
            <input type="file" name="file" class="block">
            <input type="submit" value="Send" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
          </form>
        </div>
      <?php endif; ?>

      <div class="mt-6">
        <h3 class="text-lg font-semibold">Help</h3>
        <ul class="list-disc list-inside">
          <li><a href="./help/tickets" target="_blank" class="text-blue-600 hover:underline">Ticket Fields</a></li>
          <li><a href="./help/archives" target="_blank" class="text-blue-600 hover:underline">Archives</a></li>
          <li><a href="./help/issues" target="_blank" class="text-blue-600 hover:underline">Known Issues</a></li>
        </ul>
      </div>
    </div>
  </main>
</div>

<?php
    return ob_get_clean();
}