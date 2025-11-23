<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use TradingPlatform\Domain\Order\Order;

require_once __DIR__.'/../../../../database/migrations/CreateOrdersTable.php';

beforeEach(function () {
    $capsule = new Capsule;
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    (new CreateOrdersTable)->up();
});

it('creates and retrieves an order with casts', function () {
    $order = Order::create([
        'instrument_id' => 100,
        'side' => 'BUY',
        'type' => 'LIMIT',
        'validity' => 'DAY',
        'qty' => 5,
        'price' => 12.34,
        'status' => 'PENDING',
    ]);

    $found = Order::find($order->id);
    expect($found->instrument_id)->toBeInt();
    expect($found->qty)->toBeInt();
    expect($found->price)->toBeString();
    expect($found->status)->toBe('PENDING');
});
