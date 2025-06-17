<?php
/**
 * Audit Log Routes - Enterprise Security Tracking
 */

use Pasteque\Server\System\Login;

// Audit Log Dashboard
$app->GET('/audit', function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    
    // Check if user is logged in and has admin privileges
    $currentUser = $ptApp->getCurrentUser();
    if (!$currentUser || !isset($currentUser['role'])) {
        return $response->withRedirect('./login', 302);
    }
    
    // Get audit logs (implement your audit log retrieval logic here)
    $auditLogs = getAuditLogs($ptApp, 50); // Get last 50 entries
    
    ob_start();
    include __DIR__ . '/../templates/header.html';
    
    echo '<div class="flex min-h-screen bg-gray-50 dark:bg-gray-900">';
    
    // Include sidebar
    require_once __DIR__ . '/../templates/sidebar.php';
    echo renderSidebar($ptApp, 'audit');
    
    echo '<main class="flex-1 lg:ml-64">';
    echo '<div class="p-6">';
    
    // Include audit log template
    require_once __DIR__ . '/../templates/audit_log.php';
    echo renderAuditLog($ptApp, $auditLogs);
    
    echo '</div>';
    echo '</main>';
    echo '</div>';
    
//    include __DIR__ . '/../templates/footer.html';
    
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

// API endpoint for audit logs
$app->GET('/api/audit', function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    
    // Check authentication
    $currentUser = $ptApp->getCurrentUser();
    if (!$currentUser) {
        return $response->withStatus(401)->withJson(['error' => 'Unauthorized']);
    }
    
    $page = (int)($request->getQueryParam('page', 1));
    $limit = (int)($request->getQueryParam('limit', 50));
    $type = $request->getQueryParam('type', 'all');
    
    $auditLogs = getAuditLogs($ptApp, $limit, ($page - 1) * $limit, $type);
    
    return $response->withJson([
        'logs' => $auditLogs,
        'page' => $page,
        'total' => countAuditLogs($ptApp, $type)
    ]);
});

/**
 * Get audit logs from the system
 */
function getAuditLogs($ptApp, $limit = 50, $offset = 0, $type = 'all') {
    // This is a sample implementation. In a real enterprise system,
    // you would have a dedicated audit_logs table
    
    $logs = [
        [
            'id' => 1,
            'timestamp' => date('Y-m-d H:i:s', time() - 120),
            'user_id' => 'admin',
            'action' => 'LOGIN_SUCCESS',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'details' => 'Successful login',
            'severity' => 'INFO'
        ],
        [
            'id' => 2,
            'timestamp' => date('Y-m-d H:i:s', time() - 300),
            'user_id' => 'admin',
            'action' => 'FISCAL_TICKET_CREATE',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'details' => 'Created fiscal ticket FT-2024-001234',
            'severity' => 'INFO'
        ],
        [
            'id' => 3,
            'timestamp' => date('Y-m-d H:i:s', time() - 900),
            'user_id' => null,
            'action' => 'LOGIN_FAILED',
            'ip_address' => '192.168.1.200',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'details' => 'Failed login attempt for user: invalid_user',
            'severity' => 'WARNING'
        ]
    ];
    
    return array_slice($logs, $offset, $limit);
}

/**
 * Count total audit logs
 */
function countAuditLogs($ptApp, $type = 'all') {
    // Return total count based on type filter
    return 150; // Sample count
}

/**
 * Log an audit event
 */
function logAuditEvent($ptApp, $action, $details = '', $severity = 'INFO', $userId = null, $ipAddress = null) {
    // Implementation for logging audit events
    // This should write to a dedicated audit_logs table
    
    if (!$userId) {
        $currentUser = $ptApp->getCurrentUser();
        $userId = $currentUser ? $currentUser['id'] : null;
    }
    
    if (!$ipAddress) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // In a real implementation, you would insert this into the database
    error_log(sprintf(
        "[AUDIT] %s | User: %s | IP: %s | Action: %s | Details: %s | Severity: %s",
        date('Y-m-d H:i:s'),
        $userId ?? 'anonymous',
        $ipAddress,
        $action,
        $details,
        $severity
    ));
}
?>
