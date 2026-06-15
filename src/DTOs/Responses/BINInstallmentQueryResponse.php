<?php

namespace Emreyilmaz99\SanalPos\DTOs\Responses;

use Emreyilmaz99\SanalPos\DTOs\Installment;

class BINInstallmentQueryResponse
{
    public function __construct(
        public bool $confirm = false,
        /** @var Installment[]|null */
        public ?array $installment_list = null,
        public ?array $private_response = null,
    ) {}
}
