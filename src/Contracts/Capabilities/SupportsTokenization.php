<?php

namespace EvrenOnur\SanalPos\Contracts\Capabilities;

use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\ChargeStoredCardRequest;
use EvrenOnur\SanalPos\DTOs\Requests\DeleteStoredCardRequest;
use EvrenOnur\SanalPos\DTOs\Requests\StoreCardRequest;
use EvrenOnur\SanalPos\DTOs\Responses\DeleteStoredCardResponse;
use EvrenOnur\SanalPos\DTOs\Responses\SaleResponse;
use EvrenOnur\SanalPos\DTOs\Responses\StoreCardResponse;

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
