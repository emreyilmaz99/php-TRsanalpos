<?php

namespace Emreyilmaz99\SanalPos\DTOs\Requests;

class DeleteStoredCardRequest
{
    public function __construct(
        public string $customer_id,
        public string $card_token,
        public ?string $card_user_key = null,
    ) {}

    /** @return list<string> */
    public function validate(): array
    {
        $errors = [];
        if ($this->customer_id === '') {
            $errors[] = 'customer_id zorunlu.';
        }
        if ($this->card_token === '') {
            $errors[] = 'card_token zorunlu.';
        }

        return $errors;
    }
}
