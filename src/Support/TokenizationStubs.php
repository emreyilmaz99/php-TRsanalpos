<?php

namespace EvrenOnur\SanalPos\Support;

use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\ChargeStoredCardRequest;
use EvrenOnur\SanalPos\DTOs\Requests\DeleteStoredCardRequest;
use EvrenOnur\SanalPos\DTOs\Requests\StoreCardRequest;
use EvrenOnur\SanalPos\DTOs\Responses\DeleteStoredCardResponse;
use EvrenOnur\SanalPos\DTOs\Responses\SaleResponse;
use EvrenOnur\SanalPos\DTOs\Responses\StoreCardResponse;
use EvrenOnur\SanalPos\Enums\ResponseStatus;
use EvrenOnur\SanalPos\Enums\SaleResponseStatus;

/**
 * VirtualPOSServiceInterface'in tokenization metotları için varsayılan stub'lar.
 *
 * `AbstractGateway` bu trait'e ihtiyaç duymaz (kendi içinde tanımlı), ancak
 * AbstractGateway'den türemeyen base class'lar (AbstractNestpayGateway,
 * AbstractCCPaymentGateway, AbstractPaytenGateway) bu trait'i `use` ederek
 * BC'yi korur.
 *
 * Gerçek tokenization desteği olan gateway'ler `SupportsTokenization` marker'ını
 * implement edip bu metotları override eder.
 */
trait TokenizationStubs
{
    public function storeCard(StoreCardRequest $request, MerchantAuth $auth): StoreCardResponse
    {
        return new StoreCardResponse(
            status: ResponseStatus::Error,
            message: 'Bu sanal pos için kart saklama (tokenization) şuan desteklenmiyor.',
        );
    }

    public function chargeStoredCard(ChargeStoredCardRequest $request, MerchantAuth $auth): SaleResponse
    {
        return new SaleResponse(
            status: SaleResponseStatus::Error,
            message: 'Bu sanal pos için saklı kart ile çekim şuan desteklenmiyor.',
            order_number: $request->order_number,
        );
    }

    public function deleteStoredCard(DeleteStoredCardRequest $request, MerchantAuth $auth): DeleteStoredCardResponse
    {
        return new DeleteStoredCardResponse(
            status: ResponseStatus::Error,
            message: 'Bu sanal pos için saklı kart silme şuan desteklenmiyor.',
        );
    }
}
