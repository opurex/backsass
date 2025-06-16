<?php

$app->GET('/', function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $body = $response->getBody();
    $body->write(file_get_contents(__DIR__ . '/../templates/header.html'));
    require_once(__DIR__ . '/../templates/home.php');
    $content = render($ptApp, null);
    $body->write($content);
    $body->write(file_get_contents(__DIR__ . '/../templates/footer.html'));
    return $response;
});
