<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\Customer;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

/**
 * GET customerGetAllGet
 * Summary:
 * Notes: Get an array of all Customers
 * Output-Formats: [application/json]
 */
$app->GET('/api/customer/getAll',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'customer',
            'getAll'));
});


/**
 * GET customerGetTopGet
 * Summary:
 * Notes: Get top (limit default 10) customer sorted by count of tickets
 * Output-Formats: [application/json]
 */
$app->GET('/api/customer/getTop',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'customer',
            'getTop'));
});


/**
 * GET customerIdGet
 * Summary:
 * Notes: Get a Customer
 * Output-Formats: [application/json]
 */
$app->GET('/api/customer/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'customer', 'get',
            $args['id']));
});

/** Create/update a customer without changing it's balance. */
$app->POST('/api/customer',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $structCust = $request->getParsedBody();
    if (!empty($structCust['customers'])) {
        // Only here for backward compatibility, see oldApiCustomer below.
        return oldApiCustomer($ptApp, $response, $structCust['customers']);
    }
    if (array_key_exists('expireDate', $structCust)) {
        $expireDate = DateUtils::readDate($structCust['expireDate']);
        if ($expireDate === false) {
            $loadKey = null;
            if (!empty($structCust['id'])) {
                $loadKey = ['id' => $structCust['id']];
            } else {
                $loadKey = Customer::getLoadKey($structCust);
            }
            $e = new InvalidFieldException(InvalidFieldException::CSTR_INVALID_DATE,
                    Customer::class, 'expireDate',
                    $loadKey, $structCust['expireDate']);
            return $response->reject($e, 'Invalid expireDate');
        }
        $structCust['expireDate'] = $expireDate;
    }
    $customer = null;
    if (!empty($structCust['id'])) {
        $customer = Customer::loadFromId($structCust['id'], $ptApp->getDao());
    }
    if ($customer === null) {
        $customer = new Customer();
    }
    try {
        $customer->merge($structCust, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'customer', 'write',
            $customer));
});

/** @deprecated
 * This is the POST api/customer route with 'customers' as POST parameter.
 * It is there until it is removed from pasteque-android which uses it.
 */
function oldApiCustomer($ptApp, $response, $jsonCusts) {
    $structCusts = json_decode($jsonCusts, true);
    if ($structCusts === null) {
        return $response->withStatus(400, 'Unable to parse input data');
    }
    // Fill the array.
    $customers = [];
    foreach ($structCusts as $strC) {
        if ($strC['tariffArea'] == "0") {
            // There is no tariff area in the form and Android sends shit.
            $strC['tariffArea'] = null;
        }
        $cust = null;
        if (!empty($strC['id'])) {
            $cust = Customer::loadFromId($strC['id'], $ptApp->getDao());
        }
        if ($cust === null) {
            $cust = new Customer();
        }
        try {
            $cust->merge($strC, $ptApp->getDao());
        } catch (InvalidFieldException $e) {
            return $response->reject($e);
        }
        $customers[] = $cust;
        // There is no expireDate in the form from Android, so ignore it.
    }
    $res = APICaller::run($ptApp, 'customer', 'write', [$customers]);
    if ($res->getStatus() != APIResult::STATUS_CALL_OK) {
        return $response->withApiResult($res);
    }
    $ids = [];
    if (is_array($res->getContent())) {
        foreach ($res->getContent() as $cust) {
            $ids[] = $cust->getId();
        }
        if (count($ids) == 1) {
            $ids = $ids[0];
        }
    } else {
        $ids = $res->getContent()->getId();
    }
    $newRes = APIResult::success($ids);
    return $response->withApiResult($newRes);
}

$app->PATCH('/api/customer/{id}/balance/{balance}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    if (!is_numeric($args['balance'])) {
        return $response->withStatus(400, 'Invalid balance');
    }
    $custId = $args['id'];
    $balance = floatval($args['balance']);
    $cust = Customer::loadFromId($custId, $ptApp->getDao());
    if ($cust === null) {
        $e = new RecordNotFoundException(Customer::class,
                ['id' => $args['id']]);
        return $response->notFound($e, 'Customer not found');
    }
    return $response->withAPIResult(APICaller::run($ptApp, 'customer',
            'setBalance', [$custId, $balance]));
});
