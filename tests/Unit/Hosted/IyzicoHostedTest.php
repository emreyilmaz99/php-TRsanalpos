<?php

/**
 * Iyzico CheckoutForm (hosted) testleri.
 *
 * Mevcut VposDocumentationTest deseniyle: gerçek API çağrısı yapılmaz; sadece
 * DTO inşası, serialization ve gateway ilişkileri doğrulanır.
 */

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentCallback;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\Responses\HostedPaymentResponse;
use Emreyilmaz99\SanalPos\DTOs\SaleInfo;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\Providers\IyzicoGateway;
use Emreyilmaz99\SanalPos\Infrastructure\Iyzico\Request\CreateCheckoutFormInitializeRequest;
use Emreyilmaz99\SanalPos\Infrastructure\Iyzico\Request\RetrieveCheckoutFormRequest;

function iyzicoAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: '9999',
        merchant_id: '',
        merchant_user: 'sandbox-api-key',
        merchant_password: 'sandbox-secret-key',
        merchant_storekey: '',
        test_platform: true,
    );
}

function iyzicoHostedRequest(array $overrides = []): HostedPaymentRequest
{
    return new HostedPaymentRequest(
        order_number: $overrides['order_number'] ?? 'ORDER-12345',
        customer_ip_address: $overrides['customer_ip_address'] ?? '1.2.3.4',
        sale_info: $overrides['sale_info'] ?? new SaleInfo(
            currency: Currency::TRY,
            amount: 150.75,
            installment: 1,
        ),
        success_url: $overrides['success_url'] ?? 'https://merchant.example/success',
        fail_url: $overrides['fail_url'] ?? 'https://merchant.example/fail',
        language: 'tr',
    );
}

it('Iyzico hosted gateway oluşturulabilir ve interface metodlarını içerir', function () {
    $gateway = new IyzicoGateway;

    expect(method_exists($gateway, 'initializeHostedPayment'))->toBeTrue();
    expect(method_exists($gateway, 'resolveHostedPayment'))->toBeTrue();
});

it('HostedPaymentRequest temel validasyonu çalışır', function () {
    $valid = iyzicoHostedRequest();
    expect($valid->validate())->toBe([]);

    $missingOrder = iyzicoHostedRequest(['order_number' => '']);
    expect($missingOrder->validate())->toContain('Sipariş numarası boş olamaz.');

    $missingSuccess = iyzicoHostedRequest(['success_url' => '']);
    expect($missingSuccess->validate())->toContain('Başarı URL\'i boş olamaz.');

    $missingFail = iyzicoHostedRequest(['fail_url' => '']);
    expect($missingFail->validate())->toContain('Hata URL\'i boş olamaz.');
});

it('CreateCheckoutFormInitializeRequest kart alanı içermez', function () {
    $req = new CreateCheckoutFormInitializeRequest;
    $req->locale = 'tr';
    $req->conversationId = 'ORDER-12345';
    $req->price = '150.75';
    $req->paidPrice = '150.75';
    $req->currency = 'TRY';
    $req->basketId = 'ORDER-12345';
    $req->paymentGroup = 'PRODUCT';
    $req->callbackUrl = 'https://merchant.example/success';

    $array = $req->toArray();

    expect($array)->toHaveKey('callbackUrl');
    expect($array)->toHaveKey('paymentGroup');
    expect($array['paymentGroup'])->toBe('PRODUCT');
    expect($array)->not->toHaveKey('paymentCard');
    expect($array)->not->toHaveKey('installment');

    $pki = $req->toPKIRequestString();
    expect($pki)->toContain('callbackUrl=https://merchant.example/success');
    expect($pki)->toContain('paymentGroup=PRODUCT');
    expect($pki)->not->toContain('paymentCard');
});

it('CreateCheckoutFormInitializeRequest taksit whitelist alanı taşıyabilir', function () {
    $req = new CreateCheckoutFormInitializeRequest;
    $req->enabledInstallments = [3, 6, 9];
    $array = $req->toArray();

    expect($array)->toHaveKey('enabledInstallments');
    expect($array['enabledInstallments'])->toBe([3, 6, 9]);
});

it('RetrieveCheckoutFormRequest token taşır', function () {
    $req = new RetrieveCheckoutFormRequest;
    $req->locale = 'tr';
    $req->conversationId = 'ORDER-12345';
    $req->token = 'abc123token';

    $array = $req->toArray();
    expect($array['token'])->toBe('abc123token');

    $pki = $req->toPKIRequestString();
    expect($pki)->toContain('token=abc123token');
});

it('HostedPaymentResponse Iyzico GET-redirect formatını destekler', function () {
    $response = new HostedPaymentResponse(
        status: ResponseStatus::Success,
        message: 'OK',
        order_number: 'ORDER-12345',
        redirect_method: 'GET',
        redirect_url: 'https://sandbox-cpp.iyzipay.com/?token=abc123',
        token: 'abc123',
    );

    expect($response->redirect_method)->toBe('GET');
    expect($response->redirect_url)->toBe('https://sandbox-cpp.iyzipay.com/?token=abc123');
    expect($response->form_fields)->toBe([]);
});

it('HostedPaymentCallback token doğrudan veya payload üzerinden taşıyabilir', function () {
    $direct = new HostedPaymentCallback(order_number: 'X', token: 'tok-A');
    expect($direct->token)->toBe('tok-A');

    $viaPayload = new HostedPaymentCallback(order_number: 'X', payload: ['token' => 'tok-B']);
    expect($viaPayload->payload['token'])->toBe('tok-B');
});
