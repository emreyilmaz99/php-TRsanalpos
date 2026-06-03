<?php

namespace EvrenOnur\SanalPos\DTOs\Responses;

use EvrenOnur\SanalPos\Enums\ResponseStatus;

/**
 * Kart saklama sonucu. `card_token` sonraki çekimlerde card_number yerine kullanılır.
 * Gateway'e göre `card_user_key` (Iyzico) gibi ikincil bir scoping anahtarı da dönebilir.
 */
class StoreCardResponse
{
    public function __construct(
        public ResponseStatus $status = ResponseStatus::Error,
        public string $message = '',
        public ?string $card_token = null,
        public ?string $card_user_key = null,
        public ?string $masked_pan = null,
        public ?string $card_brand = null,
        public ?array $private_response = null,
    ) {}
}
