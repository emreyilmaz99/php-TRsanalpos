<?php

namespace Emreyilmaz99\SanalPos\Contracts\Capabilities;

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\SaleQueryRequest;
use Emreyilmaz99\SanalPos\DTOs\Responses\SaleQueryResponse;

/**
 * Tek bir siparişin durumunu sorgulamayı destekleyen gateway'leri işaretler.
 *
 * Webhook gelmediğinde manuel durum doğrulaması için kritik. NestPay ailesinde
 * (CC5 üzerinden) destekli; diğer çoğu gateway'de henüz yok.
 */
interface SupportsSaleQuery
{
    public function saleQuery(SaleQueryRequest $request, MerchantAuth $auth): SaleQueryResponse;
}
