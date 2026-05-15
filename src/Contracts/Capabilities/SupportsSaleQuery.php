<?php

namespace EvrenOnur\SanalPos\Contracts\Capabilities;

use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\SaleQueryRequest;
use EvrenOnur\SanalPos\DTOs\Responses\SaleQueryResponse;

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
