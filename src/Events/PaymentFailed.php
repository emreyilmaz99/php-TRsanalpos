<?php

namespace EvrenOnur\SanalPos\Events;

use EvrenOnur\SanalPos\DTOs\MerchantAuth;

class PaymentFailed extends PaymentEvent
{
    public function __construct(
        string $bankCode,
        string $orderNumber,
        public readonly string $errorMessage,
        public readonly ?string $errorCode = null,
        ?MerchantAuth $auth = null,
        array $context = [],
    ) {
        parent::__construct($bankCode, $orderNumber, $auth, $context);
    }
}
