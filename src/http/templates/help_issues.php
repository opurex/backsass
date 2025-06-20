<?php

function render($ptApp, $data) {
    $ret = '<h1>Known Issues</h1>';
    $ret .= "<p>Since fiscal tickets are immutable, fixes made to the API cannot be retroactive. Some inconsistencies may then appear on certain tickets. This page groups known problems on previous versions as well as their impact on records. The <em>version</em> field of fiscal tickets allows identifying the API version at the time of recording to check if an inconsistency can be explained by bugs.</p>";
    $ret .= "<h2>Versions 8.2 and earlier</h2>";
    $ret .= "<h3><em>custBalance</em> Calculation</h3>";
    $ret .= "<p>The <em>custBalance</em> field of Z tickets only takes into account positive amounts for Z tickets recorded with the Desktop client. The customer balance itself is not affected and is calculated correctly ticket by ticket.</p>";
    $ret .= "<p>A script is available to list and correct regular records by recalculating Z ticket balances from session tickets.</p>";
    $ret .= "<h2>Versions 8.1 and earlier</h2>";
    $ret .= "<h3>Perpetual Revenue</h3>";
    $ret .= "<p>The <em>csPerpetual</em> field is not present in Z tickets. When updating the API, the perpetual total was recalculated for each cash register for subsequent sessions and was recalculated for past regular records.</p>";
    return $ret;
}
