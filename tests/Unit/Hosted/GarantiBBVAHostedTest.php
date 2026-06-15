<?php

/**
 * Garanti BBVA "Güvenli Ortak Ödeme Sayfası" (3D_OOS_PAY / hosted) testleri.
 *
 * Hash algoritması production-doğrulanmış GarantiHostedProvider'dan port edildi:
 *   securityData = upper(SHA1(password + zeropad9(terminalId)))
 *   secure3dhash = upper(SHA512(
 *     terminalid + orderid + amount + currency + suc + fail + txntype +
 *     installment + storeKey + securityData
 *   ))
 *
 * Callback hash: base64(SHA1(hashparamsval + storeKey)).
 *
 * MerchantAuth mapping: merchant_id=MerchantID, merchant_user=TerminalID,
 * merchant_password=ProvisionPassword, merchant_storekey=StoreKey.
 */

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentCallback;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\SaleInfo;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Enums\SaleResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\Banks\GarantiBBVAGateway;
use Emreyilmaz99\SanalPos\Support\StringHelper;

function garantiHostedAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: '0062',
        merchant_id: '7000679',
        merchant_user: '30691297',
        merchant_password: '123qweASD/',
        merchant_storekey: '12345678',
        test_platform: true,
    );
}

function garantiHostedRequest(): HostedPaymentRequest
{
    return new HostedPaymentRequest(
        order_number: 'ORDER-GAR-1',
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 200.00, installment: 1),
        success_url: 'https://merchant.example/ok',
        fail_url: 'https://merchant.example/fail',
    );
}

it('Garanti hosted gt3dengine\'a POST eder ve 3D_OOS_PAY seviyesi kullanır', function () {
    $gw = new GarantiBBVAGateway;
    $res = $gw->initializeHostedPayment(garantiHostedRequest(), garantiHostedAuth());

    expect($res->status)->toBe(ResponseStatus::Success);
    expect($res->redirect_method)->toBe('POST');
    expect($res->redirect_url)->toContain('gt3dengine');
    expect($res->form_fields['secure3dsecuritylevel'])->toBe('3D_OOS_PAY');
    expect($res->form_fields['apiversion'])->toBe('512');
    expect($res->form_fields['txntype'])->toBe('sales');
});

it('Garanti hosted form kart alanı içermez, gerekli alanları taşır', function () {
    $gw = new GarantiBBVAGateway;
    $res = $gw->initializeHostedPayment(garantiHostedRequest(), garantiHostedAuth());

    expect($res->form_fields)->not->toHaveKey('cardnumber');
    expect($res->form_fields)->not->toHaveKey('cardcvv2');
    expect($res->form_fields)->not->toHaveKey('cardexpiredatemonth');

    // Garanti'nin zorunlu alanları
    expect($res->form_fields)->toHaveKey('terminalid');
    expect($res->form_fields)->toHaveKey('terminalmerchantid');
    expect($res->form_fields)->toHaveKey('txntimestamp');
    expect($res->form_fields)->toHaveKey('mode');
    expect($res->form_fields['mode'])->toBe('TEST');
});

it('Garanti hosted tek çekimde txninstallmentcount=1 gönderir (boş değil)', function () {
    $gw = new GarantiBBVAGateway;
    $res = $gw->initializeHostedPayment(garantiHostedRequest(), garantiHostedAuth());

    expect($res->form_fields['txninstallmentcount'])->toBe('1');
});

it('Garanti hosted secure3dhash SHA512 recipe\'ini doğru hesaplar', function () {
    $gw = new GarantiBBVAGateway;
    $auth = garantiHostedAuth();
    $req = garantiHostedRequest();
    $res = $gw->initializeHostedPayment($req, $auth);

    $f = $res->form_fields;
    $amount = StringHelper::toKurus($req->sale_info->amount);

    $securityData = strtoupper(hash('sha1',
        $auth->merchant_password . str_pad($auth->merchant_user, 9, '0', STR_PAD_LEFT)
    ));

    $expected = strtoupper(hash('sha512', implode('', [
        $f['terminalid'],
        $f['orderid'],
        $f['txnamount'],
        $f['txncurrencycode'],
        $f['successurl'],
        $f['errorurl'],
        $f['txntype'],
        $f['txninstallmentcount'],
        $auth->merchant_storekey,
        $securityData,
    ])));

    expect($f['secure3dhash'])->toBe($expected);
    expect($f['txnamount'])->toBe($amount);
});

it('Garanti hosted resolveHostedPayment geçerli hash + Approved → Success', function () {
    $gw = new GarantiBBVAGateway;
    $auth = garantiHostedAuth();
    $storeKey = $auth->merchant_storekey;

    // Simüle edilmiş callback: hashparamsval olarak hazır gelir
    $hashParamsVal = 'ORDER-GAR-1' . '20000' . '00' . '1' . 'Approved';
    $hash = base64_encode(sha1($hashParamsVal . $storeKey, true));

    $payload = [
        'oid' => 'ORDER-GAR-1',
        'mdstatus' => '1',
        'procreturncode' => '00',
        'response' => 'Approved',
        'authcode' => 'AUTH-9988',
        'hashparamsval' => $hashParamsVal,
        'hash' => $hash,
    ];
    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'ORDER-GAR-1', payload: $payload), $auth);

    expect($res->status)->toBe(SaleResponseStatus::Success);
    expect($res->transaction_id)->toBe('AUTH-9988');
});

it('Garanti hosted resolveHostedPayment geçersiz hash callback\'i reddeder', function () {
    $gw = new GarantiBBVAGateway;
    $payload = [
        'oid' => 'X',
        'mdstatus' => '1',
        'procreturncode' => '00',
        'response' => 'Approved',
        'authcode' => 'X',
        'hashparamsval' => 'something',
        'hash' => 'WRONG_HASH',
    ];
    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'X', payload: $payload), garantiHostedAuth());

    expect($res->status)->toBe(SaleResponseStatus::Error);
    expect($res->message)->toContain('Hash doğrulaması');
});

it('Garanti hosted resolveHostedPayment mdstatus≠1 → Error', function () {
    $gw = new GarantiBBVAGateway;
    $auth = garantiHostedAuth();
    // Hash valid yapalım ki diğer koşullar test edilsin
    $hashParamsVal = 'declined';
    $hash = base64_encode(sha1($hashParamsVal . $auth->merchant_storekey, true));

    $payload = [
        'oid' => 'X',
        'mdstatus' => '0',
        'procreturncode' => '99',
        'response' => 'Declined',
        'mderrormessage' => 'auth fail',
        'hashparamsval' => $hashParamsVal,
        'hash' => $hash,
    ];
    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'X', payload: $payload), $auth);

    expect($res->status)->toBe(SaleResponseStatus::Error);
    expect($res->message)->toBe('auth fail');
});

it('Garanti hosted resolveHostedPayment hashparamsval yoksa hashparams field listesinden inşa eder', function () {
    $gw = new GarantiBBVAGateway;
    $auth = garantiHostedAuth();

    // hashparamsval YOK, sadece hashparams field listesi var
    $payload = [
        'oid' => 'ORDER-GAR-2',
        'mdstatus' => '1',
        'procreturncode' => '00',
        'response' => 'Approved',
        'authcode' => 'AUTH-X',
        'hashparams' => 'oid:procreturncode:response:',
        // hashparamsval YOK
    ];

    // Manuel inşa: oid + procreturncode + response = 'ORDER-GAR-200Approved'
    $expectedConcat = 'ORDER-GAR-2' . '00' . 'Approved';
    $payload['hash'] = base64_encode(sha1($expectedConcat . $auth->merchant_storekey, true));

    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'ORDER-GAR-2', payload: $payload), $auth);

    expect($res->status)->toBe(SaleResponseStatus::Success);
});
