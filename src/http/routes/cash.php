<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;
use \Pasteque\Server\System\DAO\DAOCondition;

/**
 * GET cashIdGet
 * Summary:
 * Notes: Get a Cash session
 * Output-Formats: [application/json]
 * @SWG\Get(
 *     path="/api/cash/{id}",
 *     @SWG\Response(response="200", description="Get a Cash session")
 * )
 */
$app->GET('/api/cashsession/{cashregisterid}/{sequence}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $apiResp = APICaller::run($ptApp, 'cashSession', 'get',
            [['cashRegister' => $args['cashregisterid'],
                    'sequence' => $args['sequence']]]);
    if ($apiResp->getStatus() == APIResult::STATUS_CALL_OK
            && $apiResp->getContent() == null) {
        $cashRegister = CashRegister::loadFromId($args['cashregisterid'],
                $ptApp->getDao());
        if ($cashRegister === null) {
            $e = new RecordNotFoundException(CashRegister::class,
                    ['id' => $args['cashregisterid']]);
            return $response->notFound($e, 'Cash register not found');
        }
    }
    return $response->withApiResult($apiResp);
});


/**
 * PUT cashPut
 * Summary:
 * Notes: Update a Cash session
 * Output-Formats: [application/json]
 */
$app->POST('/api/cash',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $data = $request->getParsedBody();
    $jsonSess = $data['session'];
    $structSess = json_decode($jsonSess, true);
    if ($structSess === null) {
        APICaller::run($ptApp, 'cashSession', 'registerGeneralInputFailure',
                [$jsonSess, 'Unable to parse input data']);
        return $response->withStatus(400, 'Unable to parse input data');
    }
    $structSess['openDate'] = DateUtils::readDate($structSess['openDate']);
    $structSess['closeDate'] = DateUtils::readDate($structSess['closeDate']);
    $session = CashSession::load($structSess, $ptApp->getDao());
    if ($session == null) {
        $session = new CashSession();
    }
    try {
        $session->merge($structSess, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        APICaller::run($ptApp, 'cashSession', 'registerGeneralInputFailure',
                [$structSess, $e]);
        return $response->reject($e);
    }
    return $response->withAPIResult(APICaller::run($ptApp, 'cashSession',
            'write', $session));
});


/**
 * GET cashSearchGet
 * Summary:
 * Notes: Search and get a array of Cash
 * Output-Formats: [application/json]
 */
$app->GET('/api/cash/search/',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $queryParams = $request->getQueryParams();
    $cashRegisterId = (empty($queryParams['cashRegister'])) ? null
            : $queryParams['cashRegister'];
    // Check mandatory dateStart
    $dateStart = (empty($queryParams['dateStart'])) ? null
            : DateUtils::readDate($queryParams['dateStart']);
    if ($dateStart === null) {
        return $response->withStatus(400, 'Missing mandatory dateStart.');
    }
    if ($dateStart === false) {
        return $response->withStatus(400, 'Invalid dateStart.');
    }
    // Get optional dateStop
    $dateStop = (empty($queryParams['dateStop'])) ? null
            : DateUtils::readDate($queryParams['dateStop']);
    // Check optional cash register
    $cashRegister = null;
    if ($cashRegisterId !== null) {
        $cashRegApiResp = APICaller::run($ptApp, 'cashRegister', 'get',
                $cashRegisterId);
        if ($cashRegApiResp->getStatus() != APIResult::STATUS_CALL_OK) {
            return $response->withApiResult($cashRegApiResp);
        }
        $cashRegister = $cashRegApiResp->getContent();
        if ($cashRegister === null) {
            return $response->withStatus(404, 'Cash register not found');
        }
    }
    // Run search and return
    $conditions = array();
    if ($cashRegister !== null) {
        $conditions[] = new DAOCondition('cashRegister', '=', $cashRegister);
    }
    $conditions[] = new DAOCondition('openDate', '>=', $dateStart);
    if ($dateStop !== null) {
        $conditions[] = new DAOCondition('openDate', '<=', $dateStop);
    }
    // Always exclude non-closed cash because most of the sums are not updated.
    $conditions[] = new DAOCondition('closeDate', '!=', null);
    $searchResp = APICaller::run($ptApp, 'cashSession', 'search',
            [$conditions, null, null, 'openDate']);
    return $response->withApiResult($searchResp);
});


/**
 * GET cashZticketGet
 * Summary:
 * Notes: Get the summary of a session, like a preview of data for Z tickets.
 * Output-Formats: [application/json]
 * @SWG\Get(
 *     path="/api/cash/zticket/{id}",
 *     @SWG\Response(response="200", description="get a zticket of a cash session")
 * )
 */
$app->GET('/api/cashsession/summary/{cashregisterid}/{sequence}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $cashApiResp = APICaller::run($ptApp, 'cashSession', 'get',
            [['cashRegister' => $args['cashregisterid'],
                        'sequence' => $args['sequence']]]);
    if ($cashApiResp->getStatus() != APIResult::STATUS_CALL_OK) {
        return $response->withApiResult($cashApiResp);
    }
    $session = $cashApiResp->getContent();
    return $response->withApiResult(APICaller::run($ptApp, 'cashSession',
                    'summary', $session));
});
