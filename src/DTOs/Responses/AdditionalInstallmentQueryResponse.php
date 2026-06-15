<?php

namespace Emreyilmaz99\SanalPos\DTOs\Responses;

use Emreyilmaz99\SanalPos\DTOs\AdditionalInstallment;

class AdditionalInstallmentQueryResponse
{
    public function __construct(
        public bool $confirm = false,
        /** @var AdditionalInstallment[]|null */
        public ?array $installment_list = null,
    ) {}
}
