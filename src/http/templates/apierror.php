<?php

function render($ptApp, $data) {
    $ret = '<h2>Error</h2>';
    $ret .= '<p>Status: ' . htmlspecialchars($data->getStatus()) . '</p>';
    $ret .= '<pre>' . htmlspecialchars($data->getContent()) . '</pre>';
    return $ret;
}
