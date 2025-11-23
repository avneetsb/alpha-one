<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Portfolio\Position;

/**
 * Class ReportService
 *
 * Generates performance reports and exports data.
 * Compiles trading history, performance metrics, and equity curves into
 * standardized reports for user consumption or external analysis.
 *
 * @version 1.0.0
 */
class ReportService
{
    private RiskService $riskService;

    /**
     * ReportService constructor.
     */
    public function __construct(RiskService $riskService)
    {
        $this->riskService = $riskService;
    }

    /**
     * Generate a performance report for a date range.
     *
     * Aggregates closed positions within the specified period and calculates
     * key performance indicators (KPIs) like Net Profit, Sharpe Ratio, and Win Rate.
     *
     * @param  \DateTime  $from  Start date.
     * @param  \DateTime  $to  End date.
     * @return array Report data including metrics and trade list.
     *
     * @example Generating a report
     * ```php
     * $report = $service->generatePerformanceReport(new DateTime('2023-01-01'), new DateTime('2023-01-31'));
     * echo "Net Profit: " . $report['metrics']['net_profit'];
     * ```
     */
    public function generatePerformanceReport(\DateTime $from, \DateTime $to): array
    {
        $positions = Position::whereBetween('closed_at', [$from, $to])
            ->where('status', 'CLOSED')
            ->get();

        $returns = $positions->pluck('realized_pnl')->toArray();
        $equityCurve = $this->calculateEquityCurve($positions);

        return [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'metrics' => [
                'total_trades' => count($positions),
                'net_profit' => array_sum($returns),
                'sharpe_ratio' => $this->riskService->calculateSharpeRatio($returns),
                'sortino_ratio' => $this->riskService->calculateSortinoRatio($returns),
                'max_drawdown' => $this->riskService->calculateMaxDrawdown($equityCurve),
                'win_rate' => $this->calculateWinRate($returns),
            ],
            'trades' => $positions->toArray(),
        ];
    }

    /**
     * Export a report to a specific format.
     *
     * Converts the raw report array into a downloadable format like JSON or CSV.
     *
     * @param  array  $report  The report data.
     * @param  string  $format  The output format ('json', 'csv').
     * @return string The formatted report string.
     *
     * @example Exporting to CSV
     * ```php
     * $csvContent = $service->exportReport($report, 'csv');
     * file_put_contents('report.csv', $csvContent);
     * ```
     */
    public function exportReport(array $report, string $format = 'json'): string
    {
        if ($format === 'csv') {
            return $this->toCsv($report);
        }

        return json_encode($report, JSON_PRETTY_PRINT);
    }

    /**
     * Calculate equity curve from closed positions.
     *
     * @param  mixed  $positions  Collection of positions.
     * @return array Array of cumulative PnL values.
     */
    private function calculateEquityCurve($positions): array
    {
        $curve = [];
        $runningTotal = 0;
        foreach ($positions as $position) {
            $runningTotal += $position->realized_pnl;
            $curve[] = $runningTotal;
        }

        return $curve;
    }

    /**
     * Calculate win rate percentage.
     *
     * @param  array  $returns  Array of PnL values.
     * @return float Win rate percentage (0-100).
     */
    private function calculateWinRate(array $returns): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        $wins = count(array_filter($returns, fn ($r) => $r > 0));

        return round(($wins / count($returns)) * 100, 2);
    }

    /**
     * Convert report to CSV format.
     *
     * @return string CSV content.
     */
    private function toCsv(array $report): string
    {
        $output = fopen('php://temp', 'r+');

        // Header
        fputcsv($output, ['Metric', 'Value']);
        foreach ($report['metrics'] as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            fputcsv($output, [$key, $value]);
        }

        fputcsv($output, []); // Empty line

        // Trades
        if (! empty($report['trades'])) {
            $headers = array_keys($report['trades'][0]);
            fputcsv($output, $headers);
            foreach ($report['trades'] as $trade) {
                fputcsv($output, $trade);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
