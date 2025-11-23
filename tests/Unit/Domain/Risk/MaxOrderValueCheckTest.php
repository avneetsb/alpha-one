<?php

use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Domain\Risk\Checks\MaxOrderValueCheck;

it('passes when order value under limit', function () {
    $check = new MaxOrderValueCheck(1000.0);
    $order = new Order(['qty' => 5, 'price' => 100.0]);
    $check->check($order);
    expect(true)->toBeTrue();
});

it('fails when order value exceeds limit', function () {
    $check = new MaxOrderValueCheck(1000.0);
    $order = new Order(['qty' => 20, 'price' => 100.0]);
    expect(fn () => $check->check($order))->toThrow(Exception::class);
});
