<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

$dotenvPath = __DIR__.'/../';
if (file_exists($dotenvPath.'.env.testing')) {
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath, '.env.testing');
    $dotenv->safeLoad();
}

class PestTestingResponse
{
    public array $payload = [];

    public int $code = 200;

    public $headers;

    public function __construct()
    {
        $this->headers = new class
        {
            public function set($k, $v) {}
        };
    }

    public function json(array $payload, int $code = 200)
    {
        $this->payload = $payload;
        $this->code = $code;

        return $this;
    }
}

if (! function_exists('response')) {
    function response()
    {
        return new PestTestingResponse;
    }
}

if (! function_exists('now')) {
    function now()
    {
        return new class
        {
            public function toIso8601String()
            {
                return '2020-01-01T00:00:00Z';
            }
        };
    }
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
