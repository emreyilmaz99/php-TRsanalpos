<?php

namespace EvrenOnur\SanalPos\DTOs\Responses;

use EvrenOnur\SanalPos\Enums\ResponseStatus;

class DeleteStoredCardResponse
{
    public function __construct(
        public ResponseStatus $status = ResponseStatus::Error,
        public string $message = '',
        public ?array $private_response = null,
    ) {}
}
