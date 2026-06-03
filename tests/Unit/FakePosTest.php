<?php

use EvrenOnur\SanalPos\Contracts\Capabilities\SupportsTokenization;
use EvrenOnur\SanalPos\DTOs\CustomerInfo;
use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\ChargeStoredCardRequest;
use EvrenOnur\SanalPos\DTOs\Requests\DeleteStoredCardRequest;
use EvrenOnur\SanalPos\DTOs\Requests\SaleRequest;
use EvrenOnur\SanalPos\DTOs\Requests\StoreCardRequest;
use EvrenOnur\SanalPos\DTOs\Responses\SaleResponse;
use EvrenOnur\SanalPos\DTOs\SaleInfo;
use EvrenOnur\SanalPos\Enums\Currency;
use EvrenOnur\SanalPos\Enums\SaleResponseStatus;
use EvrenOnur\SanalPos\SanalPosClient;
use EvrenOnur\SanalPos\Services\BankService;
use EvrenOnur\SanalPos\Testing\FakeGateway;

afterEach(function () {
    SanalPosClient::fakeReset();
});

function makeAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: BankService::AKBANK,
        merchant_id: 'm',
        merchant_user: 'u',
        merchant_password: 'p',
        merchant_storekey: 's',
    );
}

function makeSaleRequest(): SaleRequest
{
    $info = new CustomerInfo(name: 'Test', surname: 'User');

    return new SaleRequest(
        order_number: 'ORDER-1',
        customer_ip_address: '127.0.0.1',
        sale_info: new SaleInfo(
            card_name_surname: 'Test User',
            card_number: '4355084355084358',
            card_expiry_month: 12,
            card_expiry_year: 2030,
            card_cvv: '123',
            installment: 1,
            amount: 10.0,
            currency: Currency::TRY,
        ),
        invoice_info: $info,
        shipping_info: $info,
    );
}

it('SanalPos::fake() aktive edilince BankService FakeGateway döner', function () {
    SanalPosClient::fake();

    $gateway = BankService::createGateway(BankService::AKBANK);

    expect($gateway)->toBeInstanceOf(FakeGateway::class);
});

it('fakeQueue ile queued response döner ve call kaydedilir', function () {
    SanalPosClient::fake();
    $expected = new SaleResponse(status: SaleResponseStatus::Success, message: 'queued', order_number: 'ORDER-1', transaction_id: 'TX-1');
    SanalPosClient::fakeQueue('sale', $expected);

    $response = SanalPosClient::sale(makeSaleRequest(), makeAuth());

    expect($response)->toBe($expected);
    SanalPosClient::assertCalled('sale');
    SanalPosClient::assertCallCount('sale', 1);
});

it('Queue boşken default Success döner', function () {
    SanalPosClient::fake();

    $response = SanalPosClient::sale(makeSaleRequest(), makeAuth());

    expect($response)->toBeInstanceOf(SaleResponse::class)
        ->and($response->status)->toBe(SaleResponseStatus::Success);
});

it('assertNothingSent hiç çağrı yokken geçer', function () {
    SanalPosClient::fake();

    SanalPosClient::assertNothingSent();

    expect(true)->toBeTrue();
});

it('assertNotCalled çağrılmamış metoda izin verir', function () {
    SanalPosClient::fake();
    SanalPosClient::sale(makeSaleRequest(), makeAuth());

    SanalPosClient::assertNotCalled('refund');
    expect(true)->toBeTrue();
});

it('assertSent predicate ile request içeriğini doğrular', function () {
    SanalPosClient::fake();
    SanalPosClient::sale(makeSaleRequest(), makeAuth());

    SanalPosClient::assertSent('sale', fn ($req) => $req->order_number === 'ORDER-1');
    expect(true)->toBeTrue();
});

it('FakeGateway tüm capability marker interface\'lerini implement eder', function () {
    SanalPosClient::fake();
    $gateway = BankService::createGateway(BankService::AKBANK);

    expect($gateway)->toBeInstanceOf(SupportsTokenization::class);
});

it('Tokenization client metotları FakeGateway üzerinden çalışır', function () {
    SanalPosClient::fake();

    $store = new StoreCardRequest(
        customer_id: 'CUST-1',
        card_number: '4355084355084358',
        expiry_month: '12',
        expiry_year: '26',
        cvv: '000',
    );
    $storeResp = SanalPosClient::storeCard($store, makeAuth());

    expect($storeResp->card_token)->not->toBeNull();

    $charge = new ChargeStoredCardRequest(
        order_number: 'ORDER-2',
        customer_id: 'CUST-1',
        card_token: $storeResp->card_token,
        amount: 50.0,
    );
    $chargeResp = SanalPosClient::chargeStoredCard($charge, makeAuth());

    expect($chargeResp)->toBeInstanceOf(SaleResponse::class)
        ->and($chargeResp->status)->toBe(SaleResponseStatus::Success);

    $delete = new DeleteStoredCardRequest(customer_id: 'CUST-1', card_token: $storeResp->card_token);
    $delResp = SanalPosClient::deleteStoredCard($delete, makeAuth());

    expect($delResp->status->name)->toBe('Success');

    SanalPosClient::assertCalled('storeCard');
    SanalPosClient::assertCalled('chargeStoredCard');
    SanalPosClient::assertCalled('deleteStoredCard');
});

it('StoreCardRequest validate boş customer_id ve hatalı kart için hata listesi döner', function () {
    $req = new StoreCardRequest(
        customer_id: '',
        card_number: '123',
        expiry_month: '13',
        expiry_year: 'abcd',
    );

    $errors = $req->validate();

    expect($errors)->not->toBeEmpty()->toContain('customer_id zorunlu.');
});
