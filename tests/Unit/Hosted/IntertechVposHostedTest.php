<?php

/**
 * Intertech VPOS (Denizbank + QNB Finansbank) hosted testleri.
 *
 * İki banka neredeyse aynı API'yi kullanır; tek fark MbrId ve auth alanları.
 * SecureType='3DPayHosting' kullanılarak kart bankanın sayfasında alınır.
 */

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentCallback;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\SaleInfo;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Enums\SaleResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\Banks\DenizbankGateway;
use Emreyilmaz99\SanalPos\Gateways\Banks\QNBFinansbankGateway;
use Emreyilmaz99\SanalPos\Support\StringHelper;

function intertechAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: '0134',
        merchant_id: 'TESTSHOP',
        merchant_user: 'USER',
        merchant_password: 'PASS',
        merchant_storekey: 'STORE',
        test_platform: true,
    );
}

function intertechHostedRequest(): HostedPaymentRequest
{
    return new HostedPaymentRequest(
        order_number: 'ORDER-INT-1',
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 50.00, installment: 1),
        success_url: 'https://merchant.example/ok',
        fail_url: 'https://merchant.example/fail',
    );
}

it('Denizbank hosted SecureType=3DPayHosting kullanır, kart alanı içermez', function () {
    $res = (new DenizbankGateway)->initializeHostedPayment(intertechHostedRequest(), intertechAuth());

    expect($res->status)->toBe(ResponseStatus::Success);
    expect($res->redirect_method)->toBe('POST');
    expect($res->redirect_url)->toContain('inter-vpos.com.tr');
    expect($res->form_fields['SecureType'])->toBe('3DPayHosting');
    expect($res->form_fields)->not->toHaveKey('Pan');
    expect($res->form_fields)->not->toHaveKey('Cvv2');
});

it('Denizbank hosted hash recipe doğrulanabilir', function () {
    $auth = intertechAuth();
    $res = (new DenizbankGateway)->initializeHostedPayment(intertechHostedRequest(), $auth);
    $f = $res->form_fields;

    $expected = StringHelper::sha1Base64(
        $f['ShopCode'] . $f['OrderId'] . $f['PurchAmount'] . $f['OkUrl'] .
        $f['FailUrl'] . $f['TxnType'] . $f['InstallmentCount'] . $f['Rnd'] .
        $auth->merchant_storekey
    );

    expect($f['Hash'])->toBe($expected);
});

it('QNB Finansbank hosted MbrId=5 ve 3DPayHosting kullanır', function () {
    $res = (new QNBFinansbankGateway)->initializeHostedPayment(intertechHostedRequest(), intertechAuth());

    expect($res->form_fields['MbrId'])->toBe('5');
    expect($res->form_fields['SecureType'])->toBe('3DPayHosting');
    expect($res->redirect_url)->toContain('qnbfinansbank.com');
});

it('Intertech VPOS resolveHostedPayment ProcReturnCode=00 → Success', function () {
    $payload = ['OrderId' => 'ORDER-INT-1', 'ProcReturnCode' => '00', 'TransId' => 'T-1'];
    $callback = new HostedPaymentCallback(order_number: 'ORDER-INT-1', payload: $payload);

    $r1 = (new DenizbankGateway)->resolveHostedPayment($callback, intertechAuth());
    expect($r1->status)->toBe(SaleResponseStatus::Success);
    expect($r1->transaction_id)->toBe('T-1');

    $r2 = (new QNBFinansbankGateway)->resolveHostedPayment($callback, intertechAuth());
    expect($r2->status)->toBe(SaleResponseStatus::Success);
    expect($r2->transaction_id)->toBe('T-1');
});

it('Intertech VPOS resolveHostedPayment hata kodunu mesaja taşır', function () {
    $payload = ['OrderId' => 'X', 'ProcReturnCode' => 'V032', 'ErrorMessage' => 'Authentication failed'];
    $callback = new HostedPaymentCallback(order_number: 'X', payload: $payload);

    $r1 = (new DenizbankGateway)->resolveHostedPayment($callback, intertechAuth());
    expect($r1->status)->toBe(SaleResponseStatus::Error);
    expect($r1->message)->toBe('Authentication failed');
});
