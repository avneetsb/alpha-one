<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;
use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Infrastructure\Broker\Dhan\DhanOrderAdapter;

beforeEach(function () {
    putenv('DHAN_CLIENT_ID=test-client');
    $_ENV['DHAN_CLIENT_ID'] = 'test-client';
});

it('maps Order payload and posts to orders', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['ok' => true])),
    ]);
    $stack = HandlerStack::create($mock);
    $client = new Client(['handler' => $stack, 'base_uri' => 'https://sandbox.dhan.co/v2/']);

    $order = new Order([
        'client_order_id' => 'CO123',
        'side' => 'BUY',
        'type' => 'LIMIT',
        'validity' => 'DAY',
        'instrument_id' => 98765,
        'qty' => 10,
        'price' => 123.45,
    ]);

    $adapter = new DhanOrderAdapter('token', $client, new NullLogger);
    $response = $adapter->placeOrder($order);

    expect($response)->toEqual(['ok' => true]);
});
