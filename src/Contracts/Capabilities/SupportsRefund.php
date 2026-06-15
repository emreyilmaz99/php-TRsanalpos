<?php

namespace Emreyilmaz99\SanalPos\Contracts\Capabilities;

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\CancelRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\RefundRequest;
use Emreyilmaz99\SanalPos\DTOs\Responses\CancelResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\RefundResponse;

/**
 * İptal ve iade işlemlerini gerçek olarak destekleyen gateway'leri işaretler.
 *
 * Default `AbstractGateway` stub'ları ResponseStatus::Error döner; bu interface
 * gerçek implementasyona sahip gateway'leri ayırır.
 *
 *   if ($gateway instanceof SupportsRefund) {
 *       $gateway->refund($req, $auth);
 *   }
 */
interface SupportsRefund
{
    public function cancel(CancelRequest $request, MerchantAuth $auth): CancelResponse;

    public function refund(RefundRequest $request, MerchantAuth $auth): RefundResponse;
}
