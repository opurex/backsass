<?php

$app->GET('/', function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $body = $response->getBody();
    
    // Check if this is an AJAX request
    $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    
    if ($isAjax) {
        // Return only content for AJAX requests
        require_once(__DIR__ . '/../templates/home.php');
        $content = renderHomeDashboardContent($ptApp);
        $body->write($content);
    } else {
        // Return full page for regular requests
        $body->write(file_get_contents(__DIR__ . '/../templates/header.html'));
        require_once(__DIR__ . '/../templates/home.php');
        $content = renderDefaultHome($ptApp, null);
        $body->write($content);
        $body->write(file_get_contents(__DIR__ . '/../templates/footer.html'));
    }
    
    return $response;
});
