<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Portfolio\Position;
use TradingPlatform\Domain\Fees\Models\FeeCalculation;
use TradingPlatform\Application\Services\RiskService;

class ReportService
{
    private RiskService $riskService;

    public function __construct(RiskService $riskService)
    {
        $this->riskService = $riskService;
    }

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

    public function exportReport(array $report, string $format = 'json'): string
    {
        if ($format === 'csv') {
            return $this->toCsv($report);
        }

        return json_encode($report, JSON_PRETTY_PRINT);
    }

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

    private function calculateWinRate(array $returns): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        $wins = count(array_filter($returns, fn($r) => $r > 0));
        return round(($wins / count($returns)) * 100, 2);
    }

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
        if (!empty($report['trades'])) {
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
