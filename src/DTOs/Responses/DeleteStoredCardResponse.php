<?php

namespace Emreyilmaz99\SanalPos\DTOs\Responses;

use Emreyilmaz99\SanalPos\Enums\ResponseStatus;

class DeleteStoredCardResponse
{
    public function __construct(
        public ResponseStatus $status = ResponseStatus::Error,
        public string $message = '',
        public ?array $private_response = null,
    ) {}
}
