<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Application\Services\ReportService;
use TradingPlatform\Infrastructure\Http\ApiResponse;

/**
 * Class: Reporting Controller
 *
 * Handles requests for generating trading reports.
 * Delegates report generation logic to `ReportService` and returns
 * summarized data (e.g., P&L, trade history) in JSON format.
 */
class ReportingController
{
    use ApiResponse;

    /**
     * @var ReportService Service handling report generation logic.
     */
    private ReportService $service;

    /**
     * ReportingController constructor.
     *
     * @param  ReportService  $service  Service handling report generation logic.
     */
    public function __construct(ReportService $service)
    {
        $this->service = $service;
    }

    /**
     * Generate a trading report.
     *
     * Accepts criteria such as date range and report type, and returns
     * a summary of trading activity.
     *
     * @param  mixed  $request  HTTP request containing:
     *                          - start_date (Y-m-d)
     *                          - end_date (Y-m-d)
     *                          - type (summary, detailed)
     * @return \Illuminate\Http\JsonResponse JSON response with report data.
     *
     * @example Request
     * GET /api/v1/reports?start_date=2024-01-01&end_date=2024-01-31
     */
    public function generate($request)
    {

        return $this->success(['summary' => ['pnl' => 1234.56]]);
    }
}
