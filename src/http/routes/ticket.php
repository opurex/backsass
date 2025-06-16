<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\PastequeException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\Model\Customer;
use \Pasteque\Server\Model\Ticket;
use \Pasteque\Server\Model\User;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;
use \Pasteque\Server\System\DAO\DAOCondition;

/** Get a single ticket. */
$app->GET('/api/ticket/{cashregister}/{number}',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $cashRegister = CashRegister::loadFromId($args['cashregister']);
    if ($cashRegister === null) {
        $e = new RecordNotFoundException(CashRegister::class,
                ['id' => $args['cashregister']]);
        return $response->notFound($e, 'Cash register not found');
    }
    $apiRes = APICaller::run($ptApp, 'ticket', 'search',
            [new DAOCondition('cashRegister', '=', $cashRegister),
                    new DAOCondition('number', '=', $args['number'])]);
    if ($apiRes->getStatus() == APIResult::STATUS_CALL_OK) {
        $ticket = $apiRes->getContent();
        if (count($ticket) > 0) {
            return $response->withAPIResult(APIResult::success($ticket[0]));
        } else {
            $e = new RecordNotFoundException(Ticket::class,
                    ['cashRegister' => $args['cashRegister'],
                            'number' => $args['number']]);
            $response->notFound($e, 'Ticket not found');
        }
    }
    return $response->withApiResult($apiRes);
});

$app->GET('/api/ticket/search',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $queryParams = $request->getQueryParams();
    // Get input
    $cashRegisterId = (empty($queryParams['cashRegister'])) ? null
            : $queryParams['cashRegister'];
    $dateStart = (empty($queryParams['dateStart'])) ? null
            : DateUtils::readDate($queryParams['dateStart']);
    $dateStop =  (empty($queryParams['dateStop'])) ? null
            : DateUtils::readDate($queryParams['dateStop']);
    $userId = (empty($queryParams['user'])) ? null
            : $queryParams['user'];
    $customerId = (empty($queryParams['customer'])) ? null
            : $queryParams['customer'];
    $offset = (empty($queryParams['offset'])) ? null
            : intval($queryParams['offset']);
    if ($offset === 0) {
        $offset = null;
    }
    $limit = (empty($queryParams['limit'])) ? null
            : intval($queryParams['limit']);
    if ($limit === 0) {
        $limit = null;
    }
    // Search criterias
    if ($dateStart === false) {
        $e = new InvalidFieldException(InvalidFieldException::CSTR_INVALID_DATE,
                null, 'dateStart', null, $queryParams['dateStart']);
        return $response->reject($e, 'Invalid dateStart');
    }
    if ($dateStop === false) {
        $e = new InvalidFieldException(InvalidFieldException::CSTR_INVALID_DATE,
                null, 'dateStop', null, $queryParams['dateStop']);
        return $response->reject($e, 'Invalid dateStop');
    }
    $cashRegister = null;
    if ($cashRegisterId !== null) {
        $cashRegister = CashRegister::loadFromId($cashRegisterId,
                $ptApp->getDao());
        if ($cashRegister === null) {
            $e = new RecordNotFoundException(CashRegister::class,
                    ['id' => $cashRegisterId]);
            return $response->notFound($e, 'Cash register not found');
        }
    }
    $customer = null;
    if ($customerId !== null) {
        $customer = Customer::loadFromId($customerId, $ptApp->getDao());
        if ($customer === null) {
            $e = new RecordNotFoundException(Customer::class,
                    ['id' => $customerId]);
            return $response->notFound($e, 'Customer not found');
        }
    }
    $user = null;
    if ($userId !== null) {
        $user = User::loadFromId($userId, $ptApp->getDao());
        if ($user === null) {
            $e = new RecordNotFoundException(User::class, ['id' => $userId]);
            return $response->notFound($e, 'User not found');
        }
    }
    $conditions = [];
    if ($cashRegister !== null) {
        $conditions[] = new DAOCondition('cashRegister', '=', $cashRegister);
    }
    if ($dateStart !== null) {
        $conditions[] = new DAOCondition('date', '>=', $dateStart);
    }
    if ($dateStop !== null) {
        $conditions[] = new DAOCondition('date', '<=', $dateStop);
    }
    if ($user !== null) {
        $conditions[] = new DAOCondition('user', '=', $user);
    }
    if ($customer !== null) {
        $conditions[] = new DAOCondition('customer', '=', $customer);
    }
    if (!empty($queryParams['count'])) {
        $tktRes = APICaller::run($ptApp, 'ticket', 'count', [$conditions]);
    } else {
        $tktRes = APICaller::run($ptApp, 'ticket', 'search',
                [$conditions, $limit, $offset]);
    }
    return $response->withApiResult($tktRes);
});

/** Get tickets from a session. */
$app->GET('/api/ticket/session/{cashregister}/{sequence}',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $queryParams = $request->getQueryParams();
    $offset = (empty($queryParams['offset'])) ? null
            : intval($queryParams['offset']);
    if ($offset === 0) {
        $offset = null;
    }
    $limit = (empty($queryParams['limit'])) ? null
            : intval($queryParams['limit']);
    if ($limit === 0) {
        $limit = null;
    }
    $cashRes = APICaller::run($ptApp, 'cashSession', 'get',
            [['cashRegister' => $args['cashregister'],
              'sequence' => $args['sequence']]]);
    if ($cashRes->getStatus() != APIResult::STATUS_CALL_OK) {
        return $response->withApiResult($cashRes);
    }
    $session = $cashRes->getContent();
    if ($session === null) {
        $cashRegister = CashRegister::loadFromId($args['cashregister'],
                $ptApp->getDao());
        if ($cashRegister == null) {
            $e = new RecordNotFoundException(CashRegister::class,
                    ['id' => $args['cashregister']]);
            return $response->notFound($e, 'Cash register not found');
        }
        $e = new RecordNotFoundException(CashSession::class,
                ['cashRegister' => $args['cashregister'],
                        'sequence' => $args['sequence']]);
        return $response->notFound($e, 'Cash session not found');
    }
    $conditions = [
            new DAOCondition('cashRegister', '=', $session->getCashRegister()),
            new DAOCondition('sequence', '=', $session->getSequence())
    ];
    if (!empty($queryParams['count'])) {
        return $response->withAPIResult(APICaller::run($ptApp, 'ticket',
                'count', [$conditions]));
    } else {
        return $response->withAPIResult(APICaller::run($ptApp, 'ticket',
                'search', [$conditions], $limit, $offset));
    }
});

/**
 * POST ticketPost
 * Summary:
 * Notes: add a ticket
 * Output-Formats: [application/json]
 */
$app->POST('/api/ticket', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $data = $request->getParsedBody();
    $jsonTkts = $data['tickets'];
    $structTkts = json_decode($jsonTkts, true);
    if ($structTkts === null) {
        APICaller::run($ptApp, 'ticket', 'registerGeneralInputFailure',
                [$jsonTkts, 'Unable to parse input data']);
        return $response->withStatus(400, 'Unable to parse input data');
    }
    $result = ['successes' => [], 'failures' => [], 'errors' => []];
    foreach ($structTkts as $structTkt) {
        try {
            $structTkt['date'] = DateUtils::readDate($structTkt['date']);
            $ticket = null;
            if (!empty($structTkt['id'])) {
                $ticket = Ticket::loadFromId($structTkt['id']);
            }
            if ($ticket === null) {
                $ticket = new Ticket();
            }
            $tktResp = ['cashRegister' => $structTkt['cashRegister'],
                    'sequence' => $structTkt['sequence'],
                    'number' => $structTkt['number']];
            try {
                $ticket->merge($structTkt, $ptApp->getDao());
            } catch (InvalidFieldException $e) {
                APICaller::run($ptApp, 'ticket', 'registerGeneralInputFailure',
                        [$structTkt, $e]);
                $err = ['input' => $structTkt, 'error' => $e->toStruct()];
                $tktResp['message'] = json_encode($err);
                $result['failures'][] = $tktResp;
                continue;
            }
            $apiRes = APICaller::run($ptApp, 'ticket', 'write', $ticket);
            switch ($apiRes->getStatus()) {
            case APIResult::STATUS_CALL_OK:
                $result['successes'][] = $tktResp;
                break;
            case APIResult::STATUS_CALL_REJECTED:
                if (is_a($apiRes->getContent(), PastequeException::class)) {
                    $tktResp['message'] = json_encode($apiRes->getContent()->toStruct());
                } else {
                    $tktResp['message'] = $apiRes->getContent();
                }
                $result['failures'][] = $tktResp;
                break;
            case APIResult::STATUS_CALL_ERROR:
            default:
                $tktResp['message'] = $apiRes->getContent();
                $result['errors'][] = $tktResp;
            }
        } catch (\UnexpectedValueException $e) {
            $tktResp = ['cashRegister' => (empty($structTkt['cashRegister'])) ?
                    null : $structTkt['cashRegister'],
                    'sequence' => (empty($structTkt['sequence'])) ?
                    null : $structTkt['sequence'],
                    'number' => (empty($structTkt['number'])) ?
                    null : $structTkt['number'],
                    'message' => $e->getMessage()];
            $result['failures'][] = $tktResp;
        }
    }
    // Always return status 200 at this point
    // to let the clients parse the content and see what was ok/wrong.
    return $response->withJson($result);
});
