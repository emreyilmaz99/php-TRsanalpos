<?php

namespace Emreyilmaz99\SanalPos\DTOs\Responses;

use Emreyilmaz99\SanalPos\DTOs\AllInstallment;

class AllInstallmentQueryResponse
{
    public function __construct(
        public bool $confirm = false,
        /** @var AllInstallment[]|null */
        public ?array $installment_list = null,
        public ?array $private_response = null,
    ) {}
}
