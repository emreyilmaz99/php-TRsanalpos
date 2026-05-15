<?php

namespace EvrenOnur\SanalPos\Contracts\Capabilities;

use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\CancelRequest;
use EvrenOnur\SanalPos\DTOs\Requests\RefundRequest;
use EvrenOnur\SanalPos\DTOs\Responses\CancelResponse;
use EvrenOnur\SanalPos\DTOs\Responses\RefundResponse;

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
