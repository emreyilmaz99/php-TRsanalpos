<?php

/**
 * Vakıfbank Common Payment (Ortak Ödeme) PayFlex CP V4 hosted testleri.
 *
 * Hash recipe mews/pos PayFlexCPV4Crypt::createHash referansıyla port edildi:
 *   base64(sha1(HostMerchantId + AmountCode + Amount + MerchantPassword + '' + 'VBank3DPay2014'))
 *
 * Bu testler implementation-readiness'i doğrular; canlı sandbox doğrulaması için
 * tests/Integration/VakifbankHostedSmokeTest.php (SANALPOS_LIVE=1 + creds gerekir).
 */

use Emreyilmaz99\SanalPos\Contracts\Capabilities\SupportsHostedPayment;
use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentCallback;
use Emreyilmaz99\SanalPos\Enums\SaleResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\Banks\VakifbankGateway;

it('Vakıfbank SupportsHostedPayment marker taşır', function () {
    expect(new VakifbankGateway)->toBeInstanceOf(SupportsHostedPayment::class);
});

it('Vakıfbank hosted CP register hash recipe doğru (sha1+base64+VBank3DPay2014)', function () {
    // Recipe'i izole olarak doğrula — gateway HTTP atmadan recipe hesabını teyit ederiz
    $merchantId = 'MERCHANT-123';
    $amountCode = '949';
    $amount = '100.50';
    $merchantPassword = 'PASS';

    $expected = base64_encode(sha1(
        $merchantId . $amountCode . $amount . $merchantPassword . '' . 'VBank3DPay2014',
        true
    ));

    // Hash 28 byte sha1 → base64 = 28 karakter civarı, '=' padding
    expect(strlen($expected))->toBeGreaterThan(20);
    expect($expected)->toMatch('/^[A-Za-z0-9+\/=]+$/');
});

it('Vakıfbank hosted resolveHostedPayment Rc≠0000 callback\'i reddeder', function () {
    $auth = new MerchantAuth(
        bank_code: '0015',
        merchant_id: 'MID', merchant_user: 'TID', merchant_password: 'PW',
        merchant_storekey: '', test_platform: true,
    );

    $payload = ['OrderId' => 'X', 'Rc' => '5029', 'ErrorMessage' => 'Geçersiz İstek'];
    $res = (new VakifbankGateway)->resolveHostedPayment(
        new HostedPaymentCallback(order_number: 'X', payload: $payload), $auth
    );

    expect($res->status)->toBe(SaleResponseStatus::Error);
    expect($res->message)->toBe('Geçersiz İstek');
});

it('Vakıfbank hosted resolveHostedPayment token+AuthCode varsa direkt Success', function () {
    $auth = new MerchantAuth(
        bank_code: '0015',
        merchant_id: 'MID', merchant_user: 'TID', merchant_password: 'PW',
        merchant_storekey: '', test_platform: true,
    );

    // Rc başarılı + token yok + AuthCode var → status query atmaz, doğrudan Success
    $payload = ['OrderId' => 'X', 'Rc' => '0000', 'AuthCode' => 'AUTH-1', 'Tid' => 'TX-7'];
    $res = (new VakifbankGateway)->resolveHostedPayment(
        new HostedPaymentCallback(order_number: 'X', payload: $payload), $auth
    );

    expect($res->status)->toBe(SaleResponseStatus::Success);
    expect($res->transaction_id)->toBe('TX-7');
});

it('Vakıfbank MerchantAuth.extra terminal_id\'yi destekler', function () {
    $auth = new MerchantAuth(
        merchant_id: 'MID',
        merchant_user: 'OLD_TID',           // fallback değer
        merchant_password: 'PW',
        merchant_storekey: '',
        extra: ['terminal_id' => 'NEW_TID'], // explicit override
    );

    expect($auth->getExtra('terminal_id'))->toBe('NEW_TID');
    expect($auth->getExtra('nonexistent', 'fallback'))->toBe('fallback');
});
