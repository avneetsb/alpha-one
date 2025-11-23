<?php

use TradingPlatform\Infrastructure\Broker\Dhan\DhanAdapter;

require_once __DIR__.'/../../../../Support/TestLogger.php';

it('fetches orders from Dhan (realtime)', function () {
    $token = $_ENV['DHAN_ACCESS_TOKEN'] ?? getenv('DHAN_ACCESS_TOKEN');
    $logger = new TestLogger('DhanAdapterTest');
    $adapter = new DhanAdapter($token, null, $logger);
    $base = $_ENV['DHAN_BASE_URI'] ?? getenv('DHAN_BASE_URI') ?? 'https://sandbox.dhan.co/v2/';
    fwrite(STDOUT, "[DhanAdapterTest] GET {$base}orders\n");
    $orders = $adapter->getOrders();
    expect($orders)->toBeArray();
    fwrite(STDOUT, "[DhanAdapterTest] GET orders success\n");
});

it('fetches positions from Dhan (realtime)', function () {
    $token = $_ENV['DHAN_ACCESS_TOKEN'] ?? getenv('DHAN_ACCESS_TOKEN');
    $logger = new TestLogger('DhanAdapterTest');
    $adapter = new DhanAdapter($token, null, $logger);
    $base = $_ENV['DHAN_BASE_URI'] ?? getenv('DHAN_BASE_URI') ?? 'https://sandbox.dhan.co/v2/';
    fwrite(STDOUT, "[DhanAdapterTest] GET {$base}positions\n");
    $positions = $adapter->getPositions();
    expect($positions)->toBeArray();
    fwrite(STDOUT, "[DhanAdapterTest] GET positions success\n");
});

it('fetches holdings from Dhan (realtime)', function () {
    $token = $_ENV['DHAN_ACCESS_TOKEN'] ?? getenv('DHAN_ACCESS_TOKEN');
    $logger = new TestLogger('DhanAdapterTest');
    $adapter = new DhanAdapter($token, null, $logger);
    $base = $_ENV['DHAN_BASE_URI'] ?? getenv('DHAN_BASE_URI') ?? 'https://sandbox.dhan.co/v2/';
    fwrite(STDOUT, "[DhanAdapterTest] GET {$base}holdings\n");
    $holdings = $adapter->getHoldings();
    expect($holdings)->toBeArray();
    fwrite(STDOUT, "[DhanAdapterTest] GET holdings success\n");
});
