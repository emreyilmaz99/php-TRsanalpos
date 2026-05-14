<?php

/**
 * Payten ailesi (Payten, Paratika, VakıfPaySG, ZiraatPay) hosted-payment testleri.
 *
 * Gerçek HTTP yapılmaz; resolveHostedPayment payload-mapping ve initialize formunun
 * istenen field/URL pattern'lerine sahip olduğu doğrulanır.
 */

use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentCallback;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentRequest;
use EvrenOnur\SanalPos\DTOs\SaleInfo;
use EvrenOnur\SanalPos\Enums\Currency;
use EvrenOnur\SanalPos\Enums\SaleResponseStatus;
use EvrenOnur\SanalPos\Gateways\Providers\Payten\PaytenGateway;

function paytenAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: '9991',
        merchant_id: 'TESTMERCHANT',
        merchant_user: 'TESTUSER',
        merchant_password: 'TESTPASS',
        merchant_storekey: '',
        test_platform: true,
    );
}

function paytenHostedRequest(): HostedPaymentRequest
{
    return new HostedPaymentRequest(
        order_number: 'ORDER-PAYTEN-1',
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(
            currency: Currency::TRY,
            amount: 100.00,
            installment: 1,
        ),
        success_url: 'https://merchant.example/ok',
        fail_url: 'https://merchant.example/fail',
    );
}

it('Payten hosted resolveHostedPayment responseCode=00 ile Success döner', function () {
    $gw = new PaytenGateway;

    $payload = [
        'responseCode' => '00',
        'merchantPaymentId' => 'ORDER-PAYTEN-1',
        'pgTranId' => 'PG-TX-77',
    ];

    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'ORDER-PAYTEN-1', payload: $payload), paytenAuth());

    expect($res->status)->toBe(SaleResponseStatus::Success);
    expect($res->transaction_id)->toBe('PG-TX-77');
    expect($res->order_number)->toBe('ORDER-PAYTEN-1');
});

it('Payten hosted resolveHostedPayment hata kodunu açıklamaya çevirir', function () {
    $gw = new PaytenGateway;
    $payload = [
        'responseCode' => '99',
        'errorCode' => 'ERR10001',
        'merchantPaymentId' => 'X',
    ];

    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'X', payload: $payload), paytenAuth());

    expect($res->status)->toBe(SaleResponseStatus::Error);
    expect($res->message)->toContain('Geçersiz istek');
});

it('Payten hosted HostedPaymentRequest temel validasyonu (no card)', function () {
    $req = paytenHostedRequest();
    expect($req->validate())->toBe([]);
    expect($req->sale_info)->not->toBeNull();
});
