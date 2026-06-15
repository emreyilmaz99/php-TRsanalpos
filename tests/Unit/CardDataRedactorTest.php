<?php

use Emreyilmaz99\SanalPos\DTOs\SaleInfo;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Gateways\Providers\IyzicoGateway;
use Emreyilmaz99\SanalPos\Support\CardDataRedactor;
use Psr\Log\NullLogger;

it('maskPan ilk 6 ve son 4 hanelidir, ortası yıldız', function () {
    expect(CardDataRedactor::maskPan('5454545454545454'))->toBe('545454******5454');
    expect(CardDataRedactor::maskPan('4022780520669303'))->toBe('402278******9303');
    expect(CardDataRedactor::maskPan(''))->toBe('');
    expect(CardDataRedactor::maskPan(null))->toBe('');
});

it('redactCvv her hane yıldız', function () {
    expect(CardDataRedactor::redactCvv('123'))->toBe('***');
    expect(CardDataRedactor::redactCvv('1234'))->toBe('****');
    expect(CardDataRedactor::redactCvv(''))->toBe('');
});

it('SaleInfo json_encode\'da kart numarası ve CVV maskelenir', function () {
    $info = new SaleInfo(
        card_name_surname: 'Test User',
        card_number: '5454545454545454',
        card_expiry_month: 12,
        card_expiry_year: 2030,
        card_cvv: '123',
        currency: Currency::TRY,
        amount: 100.0,
    );

    $json = json_encode($info);
    expect($json)->not->toContain('5454545454545454');
    expect($json)->toContain('545454******5454');
    expect($json)->not->toContain('"card_cvv":"123"');
    expect($json)->toContain('"card_cvv":"***"');
});

it('SaleInfo var_dump/print_r çıktısında (__debugInfo) maskelenir', function () {
    $info = new SaleInfo(card_number: '4022780520669303', card_cvv: '988');
    $debug = $info->__debugInfo();

    expect($debug['card_number'])->toBe('402278******9303');
    expect($debug['card_cvv'])->toBe('***');
});

it('Gateway ham card_number\'a property üzerinden hâlâ erişebilir (gateway payload için)', function () {
    // Maskeleme sadece serialization katmanında — property okuma ham veri döner
    $info = new SaleInfo(card_number: '5454545454545454', card_cvv: '123');

    expect($info->card_number)->toBe('5454545454545454');
    expect($info->card_cvv)->toBe('123');
});

it('AbstractGateway opsiyonel PSR-3 logger kabul eder', function () {
    $gw = new IyzicoGateway;
    $logger = new NullLogger;

    $gw->setLogger($logger);

    // setLogger akışkan döner
    expect($gw->setLogger($logger))->toBe($gw);
});

it('redactPayload array içindeki yaygın kart field adlarını maskeler', function () {
    // Garanti
    $garanti = ['Number' => '5454545454545454', 'CVV2' => '123', 'Amount' => '100'];
    $r = CardDataRedactor::redactPayload($garanti);
    expect($r['Number'])->toBe('545454******5454');
    expect($r['CVV2'])->toBe('***');
    expect($r['Amount'])->toBe('100');

    // NestPay
    $nestpay = ['pan' => '4022780520669303', 'cv2' => '988'];
    $r = CardDataRedactor::redactPayload($nestpay);
    expect($r['pan'])->toBe('402278******9303');
    expect($r['cv2'])->toBe('***');

    // Akbank
    $akbank = ['creditCard' => '5454545454545454', 'cvv' => '123'];
    $r = CardDataRedactor::redactPayload($akbank);
    expect($r['creditCard'])->toBe('545454******5454');
    expect($r['cvv'])->toBe('***');

    // CCPayment / Sipay
    $sipay = ['cc_no' => '5454545454545454', 'cvc' => '123', 'cc_holder_name' => 'John Doe'];
    $r = CardDataRedactor::redactPayload($sipay);
    expect($r['cc_no'])->toBe('545454******5454');
    expect($r['cvc'])->toBe('***');
    expect($r['cc_holder_name'])->toBe('John Doe'); // holder name maskelenmez
});

it('redactPayload iç içe array yapılarını recursive maskeler', function () {
    $payload = [
        'Terminal' => ['ID' => 'TID', 'HashData' => 'abc'],
        'Card' => ['Number' => '5454545454545454', 'CVV2' => '999'],
        'Transaction' => ['Amount' => '150'],
    ];

    $r = CardDataRedactor::redactPayload($payload);
    expect($r['Card']['Number'])->toBe('545454******5454');
    expect($r['Card']['CVV2'])->toBe('***');
    expect($r['Terminal']['ID'])->toBe('TID'); // ID maskelenmez
});

it('redactPayload string body (XML/JSON) içinde 12-19 hane PAN pattern\'ini bulup maskeler', function () {
    $xml = '<Card><Number>5454545454545454</Number></Card>';
    $r = CardDataRedactor::redactPayload($xml);
    expect($r)->toContain('545454******5454');
    expect($r)->not->toContain('5454545454545454');
});
