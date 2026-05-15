<?php

use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\Events\EventDispatcher;
use EvrenOnur\SanalPos\Events\PaymentFailed;
use EvrenOnur\SanalPos\Events\PaymentInitiated;
use EvrenOnur\SanalPos\Events\PaymentSucceeded;

beforeEach(function () {
    EventDispatcher::flushListeners();
});

afterEach(function () {
    EventDispatcher::flushListeners();
});

it('PaymentInitiated event listener\'a teslim edilir', function () {
    $received = null;
    EventDispatcher::listen(PaymentInitiated::class, function (PaymentInitiated $e) use (&$received) {
        $received = $e;
    });

    EventDispatcher::dispatch(new PaymentInitiated('0062', 'ORDER-1'));

    expect($received)->not->toBeNull();
    expect($received->bankCode)->toBe('0062');
    expect($received->orderNumber)->toBe('ORDER-1');
});

it('PaymentSucceeded transaction_id taşır ve listener\'a teslim olur', function () {
    $received = null;
    EventDispatcher::listen(PaymentSucceeded::class, function (PaymentSucceeded $e) use (&$received) {
        $received = $e;
    });

    EventDispatcher::dispatch(new PaymentSucceeded('0062', 'ORDER-1', 'TX-99'));

    expect($received->transactionId)->toBe('TX-99');
});

it('PaymentFailed error mesajı taşır', function () {
    $received = null;
    EventDispatcher::listen(PaymentFailed::class, function (PaymentFailed $e) use (&$received) {
        $received = $e;
    });

    EventDispatcher::dispatch(new PaymentFailed('0062', 'ORDER-1', 'Yetersiz bakiye', '51'));

    expect($received->errorMessage)->toBe('Yetersiz bakiye');
    expect($received->errorCode)->toBe('51');
});

it('Aynı event türüne birden fazla listener bağlanabilir', function () {
    $count = 0;
    EventDispatcher::listen(PaymentInitiated::class, function () use (&$count) {
        $count++;
    });
    EventDispatcher::listen(PaymentInitiated::class, function () use (&$count) {
        $count++;
    });
    EventDispatcher::listen(PaymentInitiated::class, function () use (&$count) {
        $count++;
    });

    EventDispatcher::dispatch(new PaymentInitiated('0062', 'ORDER-1'));

    expect($count)->toBe(3);
});

it('Listener exception payment akışını bozmaz (sessizce yutulur)', function () {
    EventDispatcher::listen(PaymentInitiated::class, function () {
        throw new RuntimeException('listener patladı');
    });

    // Dispatch hata fırlatmamalı
    EventDispatcher::dispatch(new PaymentInitiated('0062', 'ORDER-1'));

    expect(true)->toBeTrue(); // Buraya ulaşıyorsak başarılı
});

it('Event MerchantAuth ve context taşıyabilir', function () {
    $received = null;
    EventDispatcher::listen(PaymentInitiated::class, function (PaymentInitiated $e) use (&$received) {
        $received = $e;
    });

    $auth = new MerchantAuth(bank_code: '0062', merchant_id: 'MID');
    EventDispatcher::dispatch(new PaymentInitiated('0062', 'O-1', $auth, ['flow' => 'hosted']));

    expect($received->auth)->toBe($auth);
    expect($received->context['flow'])->toBe('hosted');
});
