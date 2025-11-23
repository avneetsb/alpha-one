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
 * **States:**
 * - PENDING: Initial state, created but not processed
 * - QUEUED: Validated and queued for submission
 * - SUBMITTED: Sent to broker
 * - PARTIALLY_FILLED: Some quantity executed
 * - FILLED: Fully executed
 * - CANCELLED: Cancelled by user or system
 * - REJECTED: Rejected by broker or validation
 * - MODIFY_REQUESTED: Modification in progress
 *
 * @version 1.0.0
 *
 * @example Valid transition
 * ```php
 * $osm = new OrderStateMachine();
 * $order = Order::find(1);
 * // Transition from PENDING to QUEUED
 * $osm->transition($order, OrderStateMachine::STATE_QUEUED);
 * ```
 * @example Handling invalid transition
 * ```php
 * try {
 *     $osm->transition($order, OrderStateMachine::STATE_FILLED);
 * } catch (\Exception $e) {
 *     // Handle invalid transition error
 *     Log::error($e->getMessage());
 * }
 * ```
 *
 * @see Order For the order model
 */
class OrderStateMachine
{
    /** Initial state, created but not processed */
    const STATE_PENDING = 'PENDING';

    /** Validated and queued for submission */
    const STATE_QUEUED = 'QUEUED';

    /** Sent to broker */
    const STATE_SUBMITTED = 'SUBMITTED';

    /** Some quantity executed */
    const STATE_PARTIALLY_FILLED = 'PARTIALLY_FILLED';

    /** Fully executed */
    const STATE_FILLED = 'FILLED';

    /** Cancelled by user or system */
    const STATE_CANCELLED = 'CANCELLED';

    /** Rejected by broker or validation */
    const STATE_REJECTED = 'REJECTED';

    /** Modification in progress */
    const STATE_MODIFY_REQUESTED = 'MODIFY_REQUESTED';

    /**
     * Allowed state transitions map.
     * Key: Current State -> Value: Array of allowed Next States
     */
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
     * Validates if the transition from the current order state to the new state
     * is allowed based on the defined transition rules. If valid, updates the
     * order status and saves it. Throws an exception if invalid.
     *
     * @param  Order  $order  The order model to transition.
     * @param  string  $newState  The target state constant.
     *
     * @throws \Exception When transition is invalid.
     *
     * @example Transitioning an order
     * ```php
     * $order = new Order(['status' => OrderStateMachine::STATE_PENDING]);
     * $osm->transition($order, OrderStateMachine::STATE_QUEUED);
     * echo $order->status; // Outputs: QUEUED
     * ```
     */
    public function transition(Order $order, string $newState): void
    {
        if (! $this->canTransition($order->status, $newState)) {
            throw new \Exception("Invalid transition from {$order->status} to {$newState}");
        }

        $order->status = $newState;
        $order->save();

        // Log transition
        // Event::dispatch(new OrderStateChanged($order));
    }

    /**
     * Check if transition from current to new state is allowed.
     *
     * @param  string  $currentState  The current state.
     * @param  string  $newState  The target state.
     * @return bool True if transition is allowed, false otherwise.
     *
     * @example Checking transition validity
     * ```php
     * if ($osm->canTransition('PENDING', 'FILLED')) {
     *     // This block will not execute
     * }
     *
     * if ($osm->canTransition('PENDING', 'QUEUED')) {
     *     // This block will execute
     * }
     * ```
     */
    public function canTransition(string $currentState, string $newState): bool
    {
        if ($currentState === $newState) {
            return true;
        }

        return in_array($newState, $this->transitions[$currentState] ?? []);
    }
}
