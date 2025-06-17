<?php

function passwordUpdTpl($ptApp, $response, $template, $data = []) {
    $body = $response->getBody();
    $body->write(file_get_contents(__DIR__ . '/../templates/header.html'));
    require_once(__DIR__ . '/../templates/' . $template);
    $content = renderHome($ptApp, $data);
    $body->write($content);
    $body->write(file_get_contents(__DIR__ . '/../templates/footer.html'));
}

$app->GET('/passwordupd/', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    passwordUpdTpl($ptApp, $response, 'passwordupd.php');
    return $response;
});

$app->POST('/passwordupd/', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $data = $request->getParsedBody();
    if (empty($data['password1']) || empty($data['password2'])) {
        passwordUpdTpl($ptApp, $response, 'passwordupd.php', ['error' => "Le mot de passe n'est pas renseigné"]);
    } elseif ($data['password1'] != $data['password2']) {
        passwordUpdTpl($ptApp, $response, 'passwordupd.php', ['error' => "Le mot de passe et la confirmation sont différents"]);
    } else {
        $hash = password_hash($data['password1'], PASSWORD_DEFAULT);
        passwordUpdTpl($ptApp, $response, 'passwordupd.php', ['hash' => $hash]);
    }
    return $response;
});
