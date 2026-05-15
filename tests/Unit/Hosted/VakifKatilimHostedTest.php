<?php

/**
 * Vakıf Katılım CommonPaymentPage (3D_HOST) testleri.
 * Recipe mews/pos VakifKatilimPosRequestDataMapper'dan port edildi.
 */

use EvrenOnur\SanalPos\Contracts\Capabilities\SupportsHostedPayment;
use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentCallback;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentRequest;
use EvrenOnur\SanalPos\DTOs\SaleInfo;
use EvrenOnur\SanalPos\Enums\Currency;
use EvrenOnur\SanalPos\Enums\ResponseStatus;
use EvrenOnur\SanalPos\Enums\SaleResponseStatus;
use EvrenOnur\SanalPos\Gateways\Banks\VakifKatilimGateway;
use EvrenOnur\SanalPos\Support\StringHelper;

function vakifKatilimAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: '0210',
        merchant_id: 'MID-123',
        merchant_user: 'USER',
        merchant_password: 'PASS',
        merchant_storekey: 'STORE_KEY',
        test_platform: true,
    );
}

function vakifKatilimHostedRequest(): HostedPaymentRequest
{
    return new HostedPaymentRequest(
        order_number: 'ORDER-VK-1',
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 50.00, installment: 1),
        success_url: 'https://merchant.example/ok',
        fail_url: 'https://merchant.example/fail',
    );
}

it('VakıfKatılım SupportsHostedPayment marker taşır', function () {
    expect(new VakifKatilimGateway)->toBeInstanceOf(SupportsHostedPayment::class);
});

it('VakıfKatılım hosted CommonPaymentPage URL\'e POST eder, PaymentType=1', function () {
    $res = (new VakifKatilimGateway)->initializeHostedPayment(vakifKatilimHostedRequest(), vakifKatilimAuth());

    expect($res->status)->toBe(ResponseStatus::Success);
    expect($res->redirect_method)->toBe('POST');
    expect($res->redirect_url)->toContain('CommonPaymentPage');
    expect($res->form_fields['PaymentType'])->toBe('1');
});

it('VakıfKatılım hosted HashPassword = sha1Base64(storeKey)', function () {
    $auth = vakifKatilimAuth();
    $res = (new VakifKatilimGateway)->initializeHostedPayment(vakifKatilimHostedRequest(), $auth);

    $expected = StringHelper::sha1Base64($auth->merchant_storekey);
    expect($res->form_fields['HashPassword'])->toBe($expected);
});

it('VakıfKatılım hosted form kart alanı içermez', function () {
    $res = (new VakifKatilimGateway)->initializeHostedPayment(vakifKatilimHostedRequest(), vakifKatilimAuth());

    foreach (['CardNumber', 'CardExpireDateYear', 'CardCVV2', 'CardHolderName'] as $f) {
        expect($res->form_fields)->not->toHaveKey($f);
    }
});

it('VakıfKatılım resolveHostedPayment ResponseCode=00 + AuthCode → Success', function () {
    $payload = [
        'MerchantOrderId' => 'ORDER-VK-1',
        'ResponseCode' => '00',
        'AuthCode' => '654321',
        'ProvisionNumber' => 'PROV-99',
    ];
    $res = (new VakifKatilimGateway)->resolveHostedPayment(
        new HostedPaymentCallback(order_number: 'ORDER-VK-1', payload: $payload),
        vakifKatilimAuth()
    );

    expect($res->status)->toBe(SaleResponseStatus::Success);
    expect($res->transaction_id)->toBe('PROV-99');
});

it('VakıfKatılım resolveHostedPayment ResponseCode≠00 → Error', function () {
    $payload = [
        'MerchantOrderId' => 'X',
        'ResponseCode' => '99',
        'ResponseMessage' => 'Banka reddi',
    ];
    $res = (new VakifKatilimGateway)->resolveHostedPayment(
        new HostedPaymentCallback(order_number: 'X', payload: $payload),
        vakifKatilimAuth()
    );

    expect($res->status)->toBe(SaleResponseStatus::Error);
    expect($res->message)->toBe('Banka reddi');
});
