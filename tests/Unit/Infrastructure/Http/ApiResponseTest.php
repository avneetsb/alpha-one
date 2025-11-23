<?php

use TradingPlatform\Infrastructure\Http\ApiResponse;

class DummyController
{
    use ApiResponse;

    public function ok($data = null, ?string $message = null, int $code = 200)
    {
        return $this->success($data, $message, $code);
    }

    public function err(string $message, string $errorCode, int $statusCode = 400, $details = null)
    {
        return $this->error($message, $errorCode, $statusCode, $details);
    }
}

it('returns standardized success payload', function () {
    $c = new DummyController;
    $resp = $c->ok(['x' => 1], 'ok', 201);
    expect($resp->payload['status'])->toBe('ok');
    expect($resp->payload['data'])->toEqual(['x' => 1]);
    expect($resp->payload['message'])->toBe('ok');
    expect($resp->code)->toBe(201);
});

it('returns standardized error payload', function () {
    $c = new DummyController;
    $resp = $c->err('Invalid', 'BAD', 400, ['field' => 'price']);
    expect($resp->payload['status'])->toBe('error');
    expect($resp->payload['error']['code'])->toBe('BAD');
    expect($resp->payload['error']['message'])->toBe('Invalid');
});
