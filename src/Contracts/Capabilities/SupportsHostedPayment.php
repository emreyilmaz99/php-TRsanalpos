<?php

namespace EvrenOnur\SanalPos\Contracts\Capabilities;

use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentCallback;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentRequest;
use EvrenOnur\SanalPos\DTOs\Responses\HostedPaymentResponse;
use EvrenOnur\SanalPos\DTOs\Responses\SaleResponse;

/**
 * Hosted (banka barındırmalı) ödeme akışını destekleyen gateway'leri işaretler.
 *
 * Bu interface'i implement eden gateway'ler kart bilgisini bankanın kendi sayfasında
 * toplar (PCI-DSS SAQ-A uyumu). Default `VirtualPOSServiceInterface` her gateway'de
 * bu metodları stub olarak barındırır; gerçek implementasyon olan gateway'ler
 * bu marker interface'i de uygular.
 *
 * Kullanım:
 *   if ($gateway instanceof SupportsHostedPayment) {
 *       $response = $gateway->initializeHostedPayment($request, $auth);
 *   }
 */
interface SupportsHostedPayment
{
    public function initializeHostedPayment(HostedPaymentRequest $request, MerchantAuth $auth): HostedPaymentResponse;

    public function resolveHostedPayment(HostedPaymentCallback $callback, MerchantAuth $auth): SaleResponse;
}
