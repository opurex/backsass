<?php

function render($ptApp, $data) {
    $ret = '<h1>Génération et lecture des archives</h1>';
    $ret .= "<p>Les archives sont des export des tickets fiscaux d'une date à une autre, signées avec le protocole OpenPGP. L'archive signée doit d'abord être déchiffrée avant de pouvoir être lue. Ce déchiffrage assure la conformité avec le fichier d'origine.</p>";
    $ret .= "<p>Les archives servent à conserver les tickets fiscaux en dehors de Pastèque, de manière autonome. Ces données devant être conservée pendant 6 ans, y compris en cas de clôture du compte Pastèque. Il revient aux utilisateurs de conserver une copie des archives, voir plusieurs pour assurer leur pérénité en cas de perte ou corruption du support.</p>";
    $ret .= "<h2>Génération d'une archive</h2>";
    $ret .= "<p>Sur la page d'accueil, indiquez une date de début et de fin que couvrira l'archive. Cette période ne peut être supérieure à un an. La demande est enregistrée et placée en attente. La génération d'archives pouvant être une opération lourde, celle-ci n'est pas effectuée immédiatement. Celles-ci peuvent être générées pendant la nuit par exemple. Consultez votre codestaire Pastèque pour plus d'informations sur les délais de génération des archives.</p>";
    $ret .= "<p>Une fois l'archive générée, la demande est effacée et l'archive elle-même devient disponible sur la page d'accueil. Chaque archive est numérotée et signée.</p>";
    $ret .= "<h2>Lecture d'une archive</h2>";
    $ret .= "<p>Après avoir téléchargé l'archive, vous obtenez un fichier archive-pasteque-#.gpg, avec # le numéro de l'archive. Ce fichier doit d'abort être déchiffré avant de pouvoir être ouvert. Le déchiffrement se fait à l'aide de la clé publique que vous a fourni le codestataire Pastèque ainsi qu'un logiciel OpenPGP, tel que <a href=\"https://www.gnupg.org\" target=\"_blank\">GnuPG</a>. Le exemples de commandes se basent sur ce logiciel.</p>";
    $ret .= "<h3>Importer la clé publique</h3>";
    $ret .= "<p>L'import de la clé publique dans le trousseau OpenPGP n'est à effectuer qu'une fois. La clé sera ensuite disponible pour toutes les archives. L'import s'effectue avec la commande <code>gpg --import &lt;clé_publique.pgp&gt;</code>.</p>";
    $ret .= "<p>Une fois importée, la clé publique est disponible pour le déchiffrement. Elle figure dans la liste des clés obtenue avec la commande <code>gpg --list-keys</code>.</p>";
    $ret .= "<h3>Déchiffrer l'archive</h3>";
    $ret .= "<p>Pour déchiffrer une archive, utilisez la commande <code>gpg --output archive_pasteque_#.zip --decrypt archive_pasteque_#.gpg</code> avec # le numéro de l'archive pour correspondre au nom du fichier. Vous obtiendrez ainsi le fichier zip correspondant à l'archive déchiffrée. Conservez le fichier <code>.gpg</code> d'origine pour pouvoir réitérer l'opération et ainsi garantir que le fichier résultant est bien conforme à l'archive originale. En effet c'est le déchiffrement avec la clé publique du prestataire qui assure la conformité du fichier avec l'archive originale.</p>";
    $ret .= "<p>Pour vérifier que l'archive est bien signée avec la clé du prestataire, utilisez la commande <code>gpg --verify archive_pasteque_#.gpg</code> et contrôlez les informations de signature.</p>";
    $ret .= "<h3>Contenu d'une archive</h3>";
    $ret .= "<p>L'archive zip contient plusieurs fichiers, chacun contenant des informations au format JSON. Le fichier <code>archive.txt</code> contient les informations relatives à l'archive elle-même :</p>";
    $ret .= "<dl>";
    $ret .= "<dt>account</dt><dd>Le nom du compte Pastèque auquel cette archive correspond. Il correspond à l'identifiant pour se connecter aux différents clients Pastèque.</dd>";
    $ret .= "<dt>dateStart</dt><dd>Le début de la période que couvre cette archive. Les archives ne sont pas nécessairement dans l'ordre chronologique.</dd>";
    $ret .= "<dt>dateStop</dt><dd>La fin de la période que couvre cette archive.</dd>";
    $ret .= "<dt>number</dt><dd>Le numéro de l'archive. Il commence à 1 et s'incrémente de 1 pour chaque archive générée. Ce numéro permet de s'assurer qu'il ne manque pas une archive dans la série.</dd>";
    $ret .= "<dt>generated</dt><dd>La date de génération de l'archive. Elle correspond à la date de la génération effective et non de la date de demande de création de l'archive.</dd>";
    $ret .= "<dt>purged</dt><dd>Indique si cette archive est le résultat d'une purge des données ou non, si c'est le cas (true), les données de tickets présents dans cette archive ont été supprimés. Les tickets Z sont toujours conservés.</dd>";
    $ret .= "</dl>";
    $ret .= "<p>Les autres fichiers de l'archive contiennent les tickets fiscaux. Ils sont només de la façon suivante : XXX-YYY-Z.txt, avec XXX le type de tickets (tickets de vente, tickets Z), YYY le numéro de séquence (l'identifiant de caisse) et Z le numéro de la série. Lorsque la séquence contient beaucoup de tickets, celle-ci est divisée en plusieurs fichiers comme pour la pagination. Chacun de ces fichiers contient les tickets fiscaux au format JSON, comme présent dans l'interface fiscale.</p>";
    return $ret;
}
