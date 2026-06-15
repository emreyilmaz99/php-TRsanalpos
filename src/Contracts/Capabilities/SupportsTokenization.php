<?php

namespace Emreyilmaz99\SanalPos\Contracts\Capabilities;

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\ChargeStoredCardRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\DeleteStoredCardRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\StoreCardRequest;
use Emreyilmaz99\SanalPos\DTOs\Responses\DeleteStoredCardResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\SaleResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\StoreCardResponse;

/**
 * Kart saklama (tokenization) destekleyen gateway'leri işaretler.
 *
 * Default `AbstractGateway` stub'ları "desteklenmiyor" hatasıyla döner; gerçek
 * implementasyon olan gateway'ler (Iyzico, Sipay, Moka, Param, ...) bu marker'ı
 * implement eder.
 *
 *   if ($gateway instanceof SupportsTokenization) {
 *       $token = $gateway->storeCard($req, $auth);
 *   }
 */
interface SupportsTokenization
{
    public function storeCard(StoreCardRequest $request, MerchantAuth $auth): StoreCardResponse;

    public function chargeStoredCard(ChargeStoredCardRequest $request, MerchantAuth $auth): SaleResponse;

    public function deleteStoredCard(DeleteStoredCardRequest $request, MerchantAuth $auth): DeleteStoredCardResponse;
}
