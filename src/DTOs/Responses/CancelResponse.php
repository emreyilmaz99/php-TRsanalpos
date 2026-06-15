<?php

namespace Emreyilmaz99\SanalPos\DTOs\Responses;

use Emreyilmaz99\SanalPos\Enums\ResponseStatus;

class CancelResponse
{
    public function __construct(
        public ResponseStatus $status = ResponseStatus::Error,
        public string $message = '',
        public float $refund_amount = 0,
        public ?array $private_response = null,
    ) {}
}
