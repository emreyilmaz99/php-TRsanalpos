<?php

use Emreyilmaz99\SanalPos\Exceptions\DuplicateRequestException;
use Emreyilmaz99\SanalPos\SanalPosClient;
use Emreyilmaz99\SanalPos\Support\IdempotencyStore;
use Emreyilmaz99\SanalPos\Support\InMemoryIdempotencyStore;

beforeEach(function () {
    SanalPosClient::setIdempotencyStore(new InMemoryIdempotencyStore);
});

afterEach(function () {
    SanalPosClient::setIdempotencyStore(null);
});

it('InMemoryIdempotencyStore aynı key\'i ikinci kez seen olarak işaretler', function () {
    $store = new InMemoryIdempotencyStore;

    expect($store->seen('order-1'))->toBeFalse();
    expect($store->seen('order-1'))->toBeTrue();
    expect($store->seen('order-2'))->toBeFalse(); // farklı key etkilenmez
});

it('InMemoryIdempotencyStore TTL geçince anahtar tekrar kullanılabilir olur', function () {
    $store = new InMemoryIdempotencyStore;

    expect($store->seen('order-1', 1))->toBeFalse();
    expect($store->seen('order-1', 1))->toBeTrue();

    sleep(2);

    expect($store->seen('order-1', 1))->toBeFalse(); // TTL geçti, yeniden "first time"
});

it('DuplicateRequestException idempotencyKey alanını taşır', function () {
    $ex = new DuplicateRequestException('my-key');

    expect($ex->idempotencyKey)->toBe('my-key');
    expect($ex->getMessage())->toContain('my-key');
});

it('SanalPosClient::idempotencyStore() varsayılan store döner', function () {
    SanalPosClient::setIdempotencyStore(null);
    $store = SanalPosClient::idempotencyStore();

    expect($store)->toBeInstanceOf(IdempotencyStore::class);
});
