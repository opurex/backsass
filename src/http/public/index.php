<?php
/**
 * Pasteque API
 * @version 1.0.0
 */
namespace Pasteque\HTTP;

use Pasteque\Server\AppContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


$projectRoot = dirname(dirname(dirname(__DIR__)));
require_once $projectRoot . '/vendor/autoload.php';

// Load configuration file

$cfgFile = $projectRoot . '/config/config.ini';
if (!is_readable($cfgFile)) {
    // Check for a moved configuration
    $envCfgFile = getenv('PT_CONFIG_' . preg_replace('/[^[:alnum:]]/', '_', $projectRoot));
    if (($envCfgFile === false) || !is_readable($envCfgFile)) {
        die('No config file found');
    }
    $cfgFile = $envCfgFile;
}

$ptApp = AppContext::loadFromConfig(parse_ini_file($cfgFile));
unset($cfgFile);


$app = new \Slim\App([
    'response' => function() { return new APIResponse(); },
    'settings' => [
        // Enable whoops
        'debug' => $ptApp->isDebugMode(),
        // Display call stack in orignal slim error when debug is off
        'displayErrorDetails' => $ptApp->isDebugMode(),
        'determineRouteBeforeAppMiddleware' => true,
        // Put Pasteque config
        'ptApp' => $ptApp
    ]
]);

$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware($app));

// Add middlewares
include('../middlewares/login_middleware.php');
$app->add($loginMiddleware);
include('../middlewares/cors_middleware.php');
$app->add($corsMiddleware);

// Routes
include('../routes/home.php');
include('../routes/password.php');
include('../routes/cash.php');
include('../routes/cashregister.php');
include('../routes/category.php');
include('../routes/currency.php');
include('../routes/customer.php');
include('../routes/discount.php');
include('../routes/discountprofile.php');
include('../routes/image.php');
include('../routes/login.php');
include('../routes/option.php');
include('../routes/paymentmode.php');
include('../routes/place.php');
include('../routes/product.php');
include('../routes/resource.php');
include('../routes/role.php');
include('../routes/sync.php');
include('../routes/tariffarea.php');
include('../routes/tax.php');
include('../routes/ticket.php');
include('../routes/user.php');
include('../routes/version.php');
include('../routes/fiscal.php');
include('../routes/audit.php');


// Run
$app->run();