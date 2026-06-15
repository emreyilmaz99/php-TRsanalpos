<?php

namespace Emreyilmaz99\SanalPos\DTOs\Responses;

use Emreyilmaz99\SanalPos\Enums\SaleResponseStatus;

class SaleResponse
{
    public function __construct(
        public SaleResponseStatus $status = SaleResponseStatus::Error,
        public string $message = '',
        public string $order_number = '',
        public ?string $transaction_id = null,
        public ?array $private_response = null,
    ) {}
}
