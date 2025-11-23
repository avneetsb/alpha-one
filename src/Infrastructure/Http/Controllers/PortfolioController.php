<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Domain\Portfolio\Position;

/**
 * Class: Portfolio Controller
 *
 * Provides REST endpoints for retrieving portfolio data.
 * Allows clients to fetch open positions, calculate unrealized P&L,
 * and view specific position details.
 */
class PortfolioController
{
    /**
     * Retrieve all open positions.
     *
     * Calculates the total unrealized P&L across all positions and returns
     * a summary along with the list of positions.
     *
     * @return array JSON-serializable array containing:
     *               - success (bool)
     *               - count (int)
     *               - total_pnl (float)
     *               - positions (array)
     *
     * @example Response
     * {
     *   "success": true,
     *   "count": 2,
     *   "total_pnl": 500.00,
     *   "positions": [...]
     * }
     */
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

            return ['error' => 'Failed to fetch positions: '.$e->getMessage()];
        }
    }

    /**
     * Retrieve details for a specific position.
     *
     * @param  string  $id  The unique identifier of the position.
     * @return array JSON-serializable array with position details or error.
     *
     * @example Request
     * GET /api/v1/positions/pos_123
     */
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
