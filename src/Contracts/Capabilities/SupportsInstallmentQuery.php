<?php

namespace Emreyilmaz99\SanalPos\Contracts\Capabilities;

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\AdditionalInstallmentQueryRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\AllInstallmentQueryRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\BINInstallmentQueryRequest;
use Emreyilmaz99\SanalPos\DTOs\Responses\AdditionalInstallmentQueryResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\AllInstallmentQueryResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\BINInstallmentQueryResponse;

/**
 * Taksit sorgulama işlemlerini destekleyen gateway'leri işaretler.
 *
 * BIN bazlı, tutar bazlı veya ek (kampanya) taksitleri sorgulamayı kapsar.
 */
interface SupportsInstallmentQuery
{
    public function binInstallmentQuery(BINInstallmentQueryRequest $request, MerchantAuth $auth): BINInstallmentQueryResponse;

    public function allInstallmentQuery(AllInstallmentQueryRequest $request, MerchantAuth $auth): AllInstallmentQueryResponse;

    public function additionalInstallmentQuery(AdditionalInstallmentQueryRequest $request, MerchantAuth $auth): AdditionalInstallmentQueryResponse;
}
