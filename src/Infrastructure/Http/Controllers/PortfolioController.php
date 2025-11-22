<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Domain\Portfolio\Position;

/**
 * Portfolio API controller
 */
class PortfolioController
{
    public function getPositions(): array
    {
        try {
            $positions = Position::all();

            $totalPnl = $positions->sum('unrealized_pnl');

            return [
                'success' => true,
                'count' => $positions->count(),
                'total_pnl' => $totalPnl,
                'positions' => $positions->toArray(),
            ];

        } catch (\Exception $e) {
            http_response_code(500);
            return ['error' => 'Failed to fetch positions: ' . $e->getMessage()];
        }
    }

    public function getPosition(string $id): array
    {
        try {
            $position = Position::findOrFail($id);

            return [
                'success' => true,
                'position' => $position->toArray(),
            ];

        } catch (\Exception $e) {
            http_response_code(404);
            return ['error' => 'Position not found'];
        }
    }
}
