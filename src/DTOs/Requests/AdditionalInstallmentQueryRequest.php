<?php

namespace Emreyilmaz99\SanalPos\DTOs\Requests;

use Emreyilmaz99\SanalPos\DTOs\SaleInfo;

class AdditionalInstallmentQueryRequest
{
    public function __construct(
        public ?SaleInfo $sale_info = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sale_info: isset($data['sale_info']) ? SaleInfo::fromArray($data['sale_info']) : null,
        );
    }
}
