<?php

namespace TradingPlatform\Domain\Order\Services;

use TradingPlatform\Domain\Order\Order;

/**
 * Order State Machine
 *
 * Enforces valid transitions between order states and persists updates.
 * Provides helper to validate transitions and raises exceptions on invalid
 * state changes. Intended to be used by order processing workflow and
 * broker adapters.
 *
 * @package TradingPlatform\Domain\Order\Services
 * @version 1.0.0
 *
 * @example Valid transition:
 * $osm->transition($order, OrderStateMachine::STATE_SUBMITTED);
 *
 * @example Invalid transition throws:
 * $osm->transition($order, OrderStateMachine::STATE_FILLED); // if not allowed
 */
class OrderStateMachine
{
    const STATE_PENDING = 'PENDING';
    const STATE_QUEUED = 'QUEUED';
    const STATE_SUBMITTED = 'SUBMITTED';
    const STATE_PARTIALLY_FILLED = 'PARTIALLY_FILLED';
    const STATE_FILLED = 'FILLED';
    const STATE_CANCELLED = 'CANCELLED';
    const STATE_REJECTED = 'REJECTED';
    const STATE_MODIFY_REQUESTED = 'MODIFY_REQUESTED';

    private array $transitions = [
        self::STATE_PENDING => [self::STATE_QUEUED, self::STATE_REJECTED],
        self::STATE_QUEUED => [self::STATE_SUBMITTED, self::STATE_REJECTED, self::STATE_CANCELLED],
        self::STATE_SUBMITTED => [self::STATE_PARTIALLY_FILLED, self::STATE_FILLED, self::STATE_CANCELLED, self::STATE_REJECTED, self::STATE_MODIFY_REQUESTED],
        self::STATE_PARTIALLY_FILLED => [self::STATE_FILLED, self::STATE_CANCELLED, self::STATE_MODIFY_REQUESTED],
        self::STATE_MODIFY_REQUESTED => [self::STATE_SUBMITTED, self::STATE_REJECTED],
    ];

    /**
     * Transition an order to a new state if valid.
     *
     * @throws \Exception When transition is invalid
     */
    public function transition(Order $order, string $newState): void
    {
        if (!$this->canTransition($order->status, $newState)) {
            throw new \Exception("Invalid transition from {$order->status} to {$newState}");
        }

        $order->status = $newState;
        $order->save();
        
        // Log transition
        // Event::dispatch(new OrderStateChanged($order));
    }

    /**
     * Check if transition from current to new state is allowed.
     */
    public function canTransition(string $currentState, string $newState): bool
    {
        if ($currentState === $newState) return true;
        return in_array($newState, $this->transitions[$currentState] ?? []);
    }
}
