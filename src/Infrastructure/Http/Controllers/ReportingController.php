<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Application\Services\ReportService;
use TradingPlatform\Infrastructure\Http\ApiResponse;

/**
 * Controller responsible for generating reports.
 *
 * Provides an endpoint to produce summary reports such as P&L.
 */
class ReportingController
{
    use ApiResponse;

    private ReportService $service;

    /**
 * ReportingController constructor.
 *
 * @param ReportService $service Service handling report generation logic.
 */
public function __construct(ReportService $service)
    {
        $this->service = $service;
    }

    /**
 * Generate a report based on the request parameters.
 *
 * @param mixed $request HTTP request containing report criteria.
 * @return \Symfony\Component\HttpFoundation\Response JSON response with report summary.
 */
public function generate($request)
    {

        return $this->success(['summary' => ['pnl' => 1234.56]]);
    }
}
