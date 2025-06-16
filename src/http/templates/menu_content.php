<?php

use \Pasteque\Server\Model\FiscalTicket;

// Simulate values (replace with real $ptApp + $data handling as needed)
$data = [
    'sequences' => ['A001', 'B002'],
    'types' => ['RECEIPT', 'REFUND'],
    'archives' => [],
    'archiverequests' => [],
    'gpg' => true,
];

// Choose section
$section = $_GET['section'] ?? '';

switch ($section) {
    case 'tickets':
        echo "<h2 class='text-xl font-bold mb-4'>Z Tickets by Register</h2>";
        echo "<ul class='list-disc list-inside'>";
        foreach ($data['sequences'] as $seq) {
            echo "<li class='mb-2'>";
            echo "<strong>$seq</strong>: ";
            echo "<a href='./sequence/{$seq}/z/' class='text-blue-600 hover:underline' target='_blank'>Z Tickets</a> ";
            echo "<a href='./sequence/{$seq}/tickets/' class='text-blue-600 hover:underline' target='_blank'>Tickets</a> ";
            foreach ($data['types'] as $type) {
                if (!in_array($type, [FiscalTicket::TYPE_ZTICKET, FiscalTicket::TYPE_TICKET])) {
                    echo "<a href='./sequence/{$seq}/other?type={$type}' class='text-blue-600 hover:underline' target='_blank'>" . htmlspecialchars($type) . "</a> ";
                }
            }
            echo "</li>";
        }
        echo "</ul>";
        break;

    case 'archives':
        echo "<h2 class='text-xl font-bold mb-4'>Archives</h2>";
        if (count($data['archives']) === 0) {
            echo "<p class='text-gray-500'>No archives have been generated.</p>";
        } else {
            echo "<ul class='list-disc list-inside'>";
            foreach ($data['archives'] as $archive) {
                echo "<li>Archive ID: " . htmlspecialchars($archive->get('number')) . "</li>";
            }
            echo "</ul>";
        }

        echo "<h3 class='text-lg mt-6 mb-2 font-semibold'>Create Archive</h3>";
        echo "<form action='createarchive' method='post' class='space-y-4'>";
        echo "<div><label>From: <input type='date' name='dateStart' class='input' required></label></div>";
        echo "<div><label>To: <input type='date' name='dateStop' class='input' required></label></div>";
        echo "<button type='submit' class='px-4 py-2 bg-primary text-white rounded'>Submit</button>";
        echo "</form>";
        break;

    case 'export':
        echo "<h2 class='text-xl font-bold mb-4'>Export Tickets</h2>";
        echo "<ul class='list-disc list-inside'>";
        echo "<li><a href='./export?period=P7D' class='text-blue-600 hover:underline'>Export 1 Week</a></li>";
        echo "<li><a href='./export?period=P14D' class='text-blue-600 hover:underline'>Export 2 Weeks</a></li>";
        echo "<li><a href='./export?period=P1M' class='text-blue-600 hover:underline'>Export 1 Month</a></li>";
        echo "</ul>";
        echo "<h3 class='font-semibold mt-4'>Custom Export Range</h3>";
        echo "<form action='export' method='get' class='space-y-2'>";
        echo "<input type='date' name='from' required class='input'> to ";
        echo "<input type='date' name='to' required class='input'>";
        echo "<br><button type='submit' class='px-4 py-2 bg-primary text-white rounded'>Export</button>";
        echo "</form>";
        break;

    case 'import':
        echo "<h2 class='text-xl font-bold mb-4'>Import Tickets</h2>";
        echo "<form action='import' method='POST' enctype='multipart/form-data' class='space-y-4'>";
        echo "<input type='file' name='file' required class='input'>";
        echo "<button type='submit' class='px-4 py-2 bg-primary text-white rounded'>Import</button>";
        echo "</form>";
        break;

    case 'help':
        echo "<h2 class='text-xl font-bold mb-4'>Help</h2>";
        echo "<ul class='list-disc list-inside text-blue-600'>";
        echo "<li><a href='./help/tickets' target='_blank'>Ticket Fields</a></li>";
        echo "<li><a href='./help/archives' target='_blank'>Archive Format</a></li>";
        echo "<li><a href='./help/issues' target='_blank'>Known Issues</a></li>";
        echo "</ul>";
        break;

    default:
        echo "<p class='text-red-500'>Unknown section.</p>";
}
