<?php

use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DateUtils;

$app->GET('/fiscal/metrics', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];

    if (!fiscalLogin($this, $ptApp, $request, $response)) {
        return $response->withRedirect('/fiscal/login');
    }

    try {
        $metrics = [];

        // 1. Total ticket count
        $ticketCountRes = APICaller::run($ptApp, 'ticket', 'count', []);
        if ($ticketCountRes->getStatus() != APIResult::STATUS_CALL_OK) {
            throw new \Exception("Failed to count tickets.");
        }
        $metrics['totalTickets'] = $ticketCountRes->getContent();

        // 2. All tickets for revenue calculation
        $ticketsRes = APICaller::run($ptApp, 'ticket', 'search', [[], 1000, 0]);
        $totalRevenue = 0;
        $recentTickets = [];

        if ($ticketsRes->getStatus() == APIResult::STATUS_CALL_OK) {
            $tickets = $ticketsRes->getContent();

            foreach ($tickets as $ticket) {
                $totalRevenue += $ticket->getFinalTaxedPrice();
            }

            usort($tickets, function($a, $b) {
                return $b->getDate() <=> $a->getDate();
            });

            $recentTickets = array_slice(array_map(function ($ticket) {
                return [
                    'number' => $ticket->getNumber(),
                    'finalTaxedPrice' => $ticket->getFinalTaxedPrice(),
                    'date' => $ticket->getDate()->format('Y-m-d H:i:s')
                ];
            }, $tickets), 0, 10);
        }

        $metrics['totalRevenue'] = $totalRevenue;
        $metrics['recentTickets'] = $recentTickets;

        // 3. Customer count
        $customerCountRes = APICaller::run($ptApp, 'customer', 'count', []);
        $metrics['totalCustomers'] = ($customerCountRes->getStatus() == APIResult::STATUS_CALL_OK)
            ? $customerCountRes->getContent() : 0;

        // 4. Active sessions
        $sessionCond = [
            new DAOCondition('openDate', 'IS NOT', null),
            new DAOCondition('closeDate', 'IS', null)
        ];
        $sessionRes = APICaller::run($ptApp, 'cashSession', 'search', [$sessionCond]);

        $metrics['activeSessions'] = ($sessionRes->getStatus() == APIResult::STATUS_CALL_OK)
            ? count($sessionRes->getContent()) : 0;

        // 5. Payment methods distribution (mocked)
        $paymentMethods = [];
        $paymentModesRes = APICaller::run($ptApp, 'paymentMode', 'search', []);
        if ($paymentModesRes->getStatus() == APIResult::STATUS_CALL_OK) {
            $modes = $paymentModesRes->getContent();
            foreach ($modes as $mode) {
                $paymentMethods[] = [
                    'label' => $mode->getLabel(),
                    'amount' => $totalRevenue * (rand(10, 40) / 100) // mocked values
                ];
            }
        }
        $metrics['paymentMethods'] = $paymentMethods;

        // 6. Daily statistics for past 7 days
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $start = new \DateTime($day . ' 00:00:00');
            $end = new \DateTime($day . ' 23:59:59');

            $dayTicketsRes = APICaller::run($ptApp, 'ticket', 'search', [[
                new DAOCondition('date', '>=', $start),
                new DAOCondition('date', '<=', $end)
            ]]);

            $dayRevenue = 0;
            $dayTicketCount = 0;

            if ($dayTicketsRes->getStatus() == APIResult::STATUS_CALL_OK) {
                $dayTickets = $dayTicketsRes->getContent();
                $dayTicketCount = count($dayTickets);
                foreach ($dayTickets as $ticket) {
                    $dayRevenue += $ticket->getFinalTaxedPrice();
                }
            }

            $dailyStats[] = [
                'date' => $day,
                'revenue' => $dayRevenue,
                'tickets' => $dayTicketCount
            ];
        }
        $metrics['dailyStats'] = $dailyStats;

        return fiscalTpl($response, 'metrics', ['metrics' => $metrics]);

    } catch (\Exception $e) {
        error_log("Metrics error: " . $e->getMessage());
        return fiscalTpl($response, 'apierror', [
            'error' => 'Failed to load metrics: ' . $e->getMessage()
        ]);
    }
});
