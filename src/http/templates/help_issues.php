<?php

function render($ptApp, $data) {
    $ret = '<h1>Problèmes connus</h1>';
    $ret .= "<p>Les tickets fiscaux étant immuables, les correctifs apportés à l'API ne peuvent pas être rétro-actifs. Certaines incohérences peuvent alors apparaître sur certains tickets. Cette page regroupe les problèmes connus sur les précédentes versions ainsi que leur impact sur les enregistrements. Le champ <em>version</em> des tickets fiscaux permet d'identifier la version de l'API au moment de l'enregistrement pour vérifier si une incohérence peut être expliquée par des bugs.</p>";
    $ret .= "<h2>Versions 8.2 et précédentes</h2>";
    $ret .= "<h3>Calcul des <em>custBalance</em></h3>";
    $ret .= "<p>Le champ <em>custBalance</em> des tickets Z ne prend en compte que les montants positifs pour les tickets Z enregistrés avec le client Desktop. La balance client elle-même n'est par contre pas affectée et est calculée correctement ticket par ticket.</p>";
    $ret .= "<p>Un script est disponible pour lister et corriger les enregistrements usuels en recalculant les balances du ticket Z à partir des tickets de la session.</p>";
    $ret .= "<h2>Versions 8.1 et précédentes</h2>";
    $ret .= "<h3>Chiffre d'affaire perpetuel</h3>";
    $ret .= "<p>Le champ <em>csPerpetual</em> n'est pas présent dans les tickets Z. Lors de la mise à jour de l'API, le total perpetuel a été recalculé pour chaque caisse pour les sessions suivantes et a été recalculé pour les enregistrements usuels passés.</p>";
    return $ret;
}
