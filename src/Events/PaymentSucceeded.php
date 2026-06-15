<?php

namespace Emreyilmaz99\SanalPos\Events;

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;

class PaymentSucceeded extends PaymentEvent
{
    public function __construct(
        string $bankCode,
        string $orderNumber,
        public readonly ?string $transactionId,
        ?MerchantAuth $auth = null,
        array $context = [],
    ) {
        parent::__construct($bankCode, $orderNumber, $auth, $context);
    }
}
