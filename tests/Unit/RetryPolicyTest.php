<?php

use EvrenOnur\SanalPos\Exceptions\CircuitOpenException;
use EvrenOnur\SanalPos\Exceptions\HttpRequestException;
use EvrenOnur\SanalPos\Support\MakesHttpRequests;
use EvrenOnur\SanalPos\Support\Retry\CircuitBreaker;
use EvrenOnur\SanalPos\Support\Retry\InMemoryCircuitBreakerStore;
use EvrenOnur\SanalPos\Support\Retry\RetryPolicy;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class RetryableHttpClient
{
    use MakesHttpRequests;

    public function setClient(Client $c): void
    {
        $this->httpClient = $c;
    }

    public function call(string $url): string
    {
        return $this->httpPostForm($url, ['x' => 1]);
    }
}

function makeMockedClient(array $queue): array
{
    $handler = new MockHandler($queue);
    $stack = HandlerStack::create($handler);
    $client = new Client(['handler' => $stack]);
    $http = new RetryableHttpClient;
    $http->setClient($client);

    return [$http, $handler];
}

it('RetryPolicy delayMs exponential + jittered ve max ile sınırlı', function () {
    $policy = new RetryPolicy(maxAttempts: 5, baseDelayMs: 100, maxDelayMs: 500);
    $delay1 = $policy->delayMs(1);
    $delay2 = $policy->delayMs(2);
    $delay9 = $policy->delayMs(9);

    expect($delay1)->toBeGreaterThanOrEqual(100)->toBeLessThanOrEqual(125)
        ->and($delay2)->toBeGreaterThanOrEqual(200)->toBeLessThanOrEqual(250)
        ->and($delay9)->toBeLessThanOrEqual(625); // max + 25% jitter
});

it('Retry 503 alınca tekrar dener ve sonunda başarılı döner', function () {
    [$http] = makeMockedClient([
        new Response(503, [], 'fail'),
        new Response(503, [], 'fail'),
        new Response(200, [], 'ok'),
    ]);
    $http->withRetry(new RetryPolicy(maxAttempts: 3, baseDelayMs: 1, maxDelayMs: 2));

    $body = $http->call('https://api.example.com/x');

    expect($body)->toBe('ok');
});

it('Retry max_attempts dolunca HttpRequestException fırlatır', function () {
    [$http] = makeMockedClient([
        new Response(503, [], 'fail'),
        new Response(503, [], 'fail'),
    ]);
    $http->withRetry(new RetryPolicy(maxAttempts: 2, baseDelayMs: 1, maxDelayMs: 2));

    expect(fn () => $http->call('https://api.example.com/x'))
        ->toThrow(HttpRequestException::class);
});

it('Retry off iken ilk hatada hemen fırlatır', function () {
    [$http] = makeMockedClient([
        new Response(503, [], 'fail'),
    ]);

    expect(fn () => $http->call('https://api.example.com/x'))
        ->toThrow(HttpRequestException::class);
});

it('Retry ConnectException için de çalışır', function () {
    [$http] = makeMockedClient([
        new ConnectException('timeout', new Request('POST', 'https://api.example.com/x')),
        new Response(200, [], 'ok'),
    ]);
    $http->withRetry(new RetryPolicy(maxAttempts: 2, baseDelayMs: 1, maxDelayMs: 2));

    expect($http->call('https://api.example.com/x'))->toBe('ok');
});

it('Circuit breaker eşiği aşılınca CircuitOpenException fırlatır', function () {
    $store = new InMemoryCircuitBreakerStore;
    $breaker = new CircuitBreaker($store, failureThreshold: 2, openSeconds: 5);

    [$http] = makeMockedClient([
        new Response(500, [], 'fail'),
        new Response(500, [], 'fail'),
    ]);
    $http->setCircuitBreaker($breaker);

    // 1. çağrı: fail (1 fail), throw HttpRequestException
    try {
        $http->call('https://api.example.com/x');
    } catch (HttpRequestException) {
    }
    // 2. çağrı: fail (2 fail → eşik), throw HttpRequestException ama breaker artık open
    try {
        $http->call('https://api.example.com/x');
    } catch (HttpRequestException) {
    }
    // 3. çağrı: breaker open → CircuitOpenException
    expect(fn () => $http->call('https://api.example.com/x'))
        ->toThrow(CircuitOpenException::class);
});

it('Circuit breaker başarılı çağrıdan sonra failure sayacını sıfırlar', function () {
    $store = new InMemoryCircuitBreakerStore;
    $breaker = new CircuitBreaker($store, failureThreshold: 3, openSeconds: 5);

    [$http] = makeMockedClient([
        new Response(500, [], 'fail'),
        new Response(200, [], 'ok'),
        new Response(500, [], 'fail'),
    ]);
    $http->setCircuitBreaker($breaker);

    try {
        $http->call('https://api.example.com/x');
    } catch (HttpRequestException) {
    }
    expect($store->failures('api.example.com'))->toBe(1);

    $http->call('https://api.example.com/x');
    expect($store->failures('api.example.com'))->toBe(0);

    try {
        $http->call('https://api.example.com/x');
    } catch (HttpRequestException) {
    }
    expect($store->failures('api.example.com'))->toBe(1); // sayaç tekrar 1
});
