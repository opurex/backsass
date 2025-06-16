<?php

use \Pasteque\Server\System\API\APIResult;

function render($ptApp, $apiResult) {
    $ret = '<h1>Import des tickets fiscaux</h1>';
    if ($apiResult->getStatus() != APIResult::STATUS_CALL_OK) {
        $ret .= "<p>L'import a échoué : " . $apiResult->getContent() . '</p>';
        return $ret;
    }
    $res = $apiResult->getContent();
    if (count($res->get('successes')) == 0 && count($res->get('failures')) == 0) {
        $ret .= "<p>Aucun ticket à importer.</p>";
        $ret .= "<p><a href=\".\">Retourner au menu</a></p>";
        return $ret;
    }
    if (count($res->get('successes')) > 0) {
        $ret .= "<p>" . count($res->get('successes')) . " tickets ont été importés avec succès.</p>";
    }
    if (count($res->get('failures')) > 0) {
        $ret .= "<p>" . count($res->get('failures')) . " tickets n'ont pu être importés :</p>";
        $ret .= '<ul>';
        foreach ($res->get('failures') as $fail) {
            $tkt = $fail->get('ticket');
            $ret .= '<li>Ticket ' . $tkt->getType() . '-' . $tkt->getSequence() . ' #' . $tkt->getNumber() . ' : ' . $fail->get('reason') . '</li>';
        }
        $ret .= '</ul>';
    }
    $ret .= "<p><a href=\".\">Retourner au menu</a></p>";
    return $ret;
}
