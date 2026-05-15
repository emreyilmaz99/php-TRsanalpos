<?php

/**
 * Capability matrix generator — her gateway için hangi metodun gerçek implementasyon,
 * hangisinin abstract'taki "desteklenmiyor" stub'ı olduğunu tablo halinde çıkarır.
 * Kullanım: php tools/capability-matrix.php
 */

require __DIR__ . '/../vendor/autoload.php';

use EvrenOnur\SanalPos\Gateways\AbstractGateway;
use EvrenOnur\SanalPos\Gateways\Banks\AkbankGateway;
use EvrenOnur\SanalPos\Gateways\Banks\DenizbankGateway;
use EvrenOnur\SanalPos\Gateways\Banks\GarantiBBVAGateway;
use EvrenOnur\SanalPos\Gateways\Banks\KuveytTurkGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\AkbankNestpayGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\AlternatifBankGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\AnadolubankGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\FinansbankNestpayGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\HalkbankGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\INGBankGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\IsBankasiGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\SekerbankGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\TurkEkonomiBankasiGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\TurkiyeFinansGateway;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\ZiraatBankasiGateway;
use EvrenOnur\SanalPos\Gateways\Banks\QNBFinansbankGateway;
use EvrenOnur\SanalPos\Gateways\Banks\VakifbankGateway;
use EvrenOnur\SanalPos\Gateways\Banks\VakifKatilimGateway;
use EvrenOnur\SanalPos\Gateways\Banks\YapiKrediBankasiGateway;
use EvrenOnur\SanalPos\Gateways\Providers\AhlpayGateway;
use EvrenOnur\SanalPos\Gateways\Providers\CCPayment\HalkOdeGateway;
use EvrenOnur\SanalPos\Gateways\Providers\CCPayment\IQmoneyGateway;
use EvrenOnur\SanalPos\Gateways\Providers\CCPayment\ParolaparaGateway;
use EvrenOnur\SanalPos\Gateways\Providers\CCPayment\PayBullGateway;
use EvrenOnur\SanalPos\Gateways\Providers\CCPayment\QNBPayGateway;
use EvrenOnur\SanalPos\Gateways\Providers\CCPayment\SipayGateway;
use EvrenOnur\SanalPos\Gateways\Providers\CCPayment\VeparaGateway;
use EvrenOnur\SanalPos\Gateways\Providers\IyzicoGateway;
use EvrenOnur\SanalPos\Gateways\Providers\MokaGateway;
use EvrenOnur\SanalPos\Gateways\Providers\ParamPosGateway;
use EvrenOnur\SanalPos\Gateways\Providers\PaynetGateway;
use EvrenOnur\SanalPos\Gateways\Providers\PayNKolayGateway;
use EvrenOnur\SanalPos\Gateways\Providers\Payten\ParatikaGateway;
use EvrenOnur\SanalPos\Gateways\Providers\Payten\PaytenGateway;
use EvrenOnur\SanalPos\Gateways\Providers\Payten\VakifPaySGateway;
use EvrenOnur\SanalPos\Gateways\Providers\Payten\ZiraatPayGateway;
use EvrenOnur\SanalPos\Gateways\Providers\TamiGateway;

$gateways = [
    '0046' => ['name' => 'Akbank', 'class' => AkbankGateway::class],
    '9046' => ['name' => 'Akbank NestPay', 'class' => AkbankNestpayGateway::class],
    '0124' => ['name' => 'Alternatif Bank', 'class' => AlternatifBankGateway::class],
    '0135' => ['name' => 'Anadolubank', 'class' => AnadolubankGateway::class],
    '0134' => ['name' => 'Denizbank', 'class' => DenizbankGateway::class],
    '0111' => ['name' => 'QNB Finansbank', 'class' => QNBFinansbankGateway::class],
    '9111' => ['name' => 'Finansbank NestPay', 'class' => FinansbankNestpayGateway::class],
    '0062' => ['name' => 'Garanti BBVA', 'class' => GarantiBBVAGateway::class],
    '0012' => ['name' => 'Halkbank', 'class' => HalkbankGateway::class],
    '0099' => ['name' => 'ING Bank', 'class' => INGBankGateway::class],
    '0064' => ['name' => 'İş Bankası', 'class' => IsBankasiGateway::class],
    '0205' => ['name' => 'Kuveyt Türk', 'class' => KuveytTurkGateway::class],
    '0032' => ['name' => 'TEB', 'class' => TurkEkonomiBankasiGateway::class],
    '0206' => ['name' => 'Türkiye Finans', 'class' => TurkiyeFinansGateway::class],
    '0015' => ['name' => 'Vakıfbank', 'class' => VakifbankGateway::class],
    '0067' => ['name' => 'Yapı Kredi', 'class' => YapiKrediBankasiGateway::class],
    '0059' => ['name' => 'Şekerbank', 'class' => SekerbankGateway::class],
    '0010' => ['name' => 'Ziraat Bankası', 'class' => ZiraatBankasiGateway::class],
    '0210' => ['name' => 'Vakıf Katılım', 'class' => VakifKatilimGateway::class],
    '9999' => ['name' => 'Iyzico', 'class' => IyzicoGateway::class],
    '9991' => ['name' => 'Moka', 'class' => MokaGateway::class],
    '9990' => ['name' => 'QNBPay (CCPayment)', 'class' => QNBPayGateway::class],
    '9988' => ['name' => 'Sipay', 'class' => SipayGateway::class],
    '9987' => ['name' => 'PayBull', 'class' => PayBullGateway::class],
    '9986' => ['name' => 'Vepara', 'class' => VeparaGateway::class],
    '9985' => ['name' => 'Parolapara', 'class' => ParolaparaGateway::class],
    '9984' => ['name' => 'IQmoney', 'class' => IQmoneyGateway::class],
    '9983' => ['name' => 'HalkOde', 'class' => HalkOdeGateway::class],
    '9982' => ['name' => 'ZiraatPay', 'class' => ZiraatPayGateway::class],
    '9981' => ['name' => 'VakıfPaySG', 'class' => VakifPaySGateway::class],
    '9980' => ['name' => 'Paratika', 'class' => ParatikaGateway::class],
    '9979' => ['name' => 'Payten', 'class' => PaytenGateway::class],
    '9978' => ['name' => 'Tami', 'class' => TamiGateway::class],
    '9977' => ['name' => 'ParamPos', 'class' => ParamPosGateway::class],
    '9976' => ['name' => 'Paynet', 'class' => PaynetGateway::class],
    '9975' => ['name' => 'PayNKolay', 'class' => PayNKolayGateway::class],
    '9974' => ['name' => 'Ahlpay', 'class' => AhlpayGateway::class],
];

$methods = [
    'sale' => 'Sale',
    'sale3DResponse' => '3D',
    'cancel' => 'Cancel',
    'refund' => 'Refund',
    'saleQuery' => 'Query',
    'initializeHostedPayment' => 'Hosted',
    'resolveHostedPayment' => 'HostedCB',
    'binInstallmentQuery' => 'BIN',
];

echo '| Banka | bank_code | ' . implode(' | ', $methods) . " |\n";
echo '|---|---|' . str_repeat('---|', count($methods)) . "\n";

foreach ($gateways as $code => $g) {
    $row = "| {$g['name']} | `{$code}` |";
    $rc = new ReflectionClass($g['class']);
    foreach (array_keys($methods) as $m) {
        if (! $rc->hasMethod($m)) {
            $row .= ' — |';

            continue;
        }
        $declaring = $rc->getMethod($m)->getDeclaringClass()->getName();
        $isDefault = ($declaring === AbstractGateway::class);
        $row .= $isDefault ? ' ❌ |' : ' ✅ |';
    }
    echo $row . "\n";
}
