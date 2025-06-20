<?php

function render($ptApp, $data) {
    $ret = '<h1>Archive Generation and Reading</h1>';
    $ret .= "<p>Archives are exports of fiscal tickets from one date to another, signed with the OpenPGP protocol. The signed archive must first be decrypted before it can be read. This decryption ensures compliance with the original file.</p>";
    $ret .= "<p>Archives are used to store fiscal tickets outside of Pasteque, independently. This data must be kept for 6 years, including in case of Pasteque account closure. It is up to users to keep a copy of the archives, or several to ensure their durability in case of loss or corruption of the medium.</p>";
    $ret .= "<h2>Generating an Archive</h2>";
    $ret .= "<p>On the home page, specify a start and end date that the archive will cover. This period cannot exceed one year. The request is recorded and queued. Since archive generation can be a heavy operation, it is not performed immediately. These can be generated at night, for example. Consult your Pasteque provider for more information on archive generation delays.</p>";
    $ret .= "<p>Once the archive is generated, the request is deleted and the archive itself becomes available on the home page. Each archive is numbered and signed.</p>";
    $ret .= "<h2>Reading an Archive</h2>";
    $ret .= "<p>After downloading the archive, you get an archive-pasteque-#.gpg file, with # being the archive number. This file must first be decrypted before it can be opened. Decryption is done using the public key provided by your Pasteque provider and an OpenPGP software, such as <a href=\"https://www.gnupg.org\" target=\"_blank\">GnuPG</a>. The command examples are based on this software.</p>";
    $ret .= "<h3>Import the Public Key</h3>";
    $ret .= "<p>Importing the public key into the OpenPGP keyring only needs to be done once. The key will then be available for all archives. Import is done with the command <code>gpg --import &lt;public_key.pgp&gt;</code>.</p>";
    $ret .= "<p>Once imported, the public key is available for decryption. It appears in the key list obtained with the command <code>gpg --list-keys</code>.</p>";
    $ret .= "<h3>Decrypt the Archive</h3>";
    $ret .= "<p>To decrypt an archive, use the command <code>gpg --output archive_pasteque_#.zip --decrypt archive_pasteque_#.gpg</code> with # being the archive number to match the file name. You will thus get the zip file corresponding to the decrypted archive. Keep the original <code>.gpg</code> file to be able to repeat the operation and thus guarantee that the resulting file is indeed compliant with the original archive. Indeed, it is the decryption with the provider's public key that ensures the file's compliance with the original archive.</p>";
    $ret .= "<p>To verify that the archive is properly signed with the provider's key, use the command <code>gpg --verify archive_pasteque_#.gpg</code> and check the signature information.</p>";
    $ret .= "<h3>Archive Content</h3>";
    $ret .= "<p>The zip archive contains several files, each containing information in JSON format. The <code>archive.txt</code> file contains information related to the archive itself:</p>";
    $ret .= "<dl>";
    $ret .= "<dt>account</dt><dd>The name of the Pasteque account to which this archive corresponds. It corresponds to the identifier to connect to different Pasteque clients.</dd>";
    $ret .= "<dt>dateStart</dt><dd>The beginning of the period covered by this archive. Archives are not necessarily in chronological order.</dd>";
    $ret .= "<dt>dateStop</dt><dd>The end of the period covered by this archive.</dd>";
    $ret .= "<dt>number</dt><dd>The archive number. It starts at 1 and increments by 1 for each generated archive. This number ensures that no archive is missing in the series.</dd>";
    $ret .= "<dt>generated</dt><dd>The archive generation date. It corresponds to the actual generation date and not to the archive creation request date.</dd>";
    $ret .= "<dt>purged</dt><dd>Indicates whether this archive is the result of a data purge or not. If so (true), the ticket data present in this archive has been deleted. Z tickets are always kept.</dd>";
    $ret .= "</dl>";
    $ret .= "<p>The other files in the archive contain the fiscal tickets. They are named as follows: XXX-YYY-Z.txt, with XXX being the ticket type (sales tickets, Z tickets), YYY the sequence number (cash register identifier) and Z the series number. When the sequence contains many tickets, it is divided into several files like pagination. Each of these files contains the fiscal tickets in JSON format, as present in the fiscal interface.</p>";
    return $ret;
}
