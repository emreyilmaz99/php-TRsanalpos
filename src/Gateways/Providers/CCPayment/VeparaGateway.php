<?php

namespace Emreyilmaz99\SanalPos\Gateways\Providers\CCPayment;

class VeparaGateway extends AbstractCCPaymentGateway
{
    protected function getTestBaseUrl(): string
    {
        return 'https://test.vepara.com.tr/ccpayment';
    }

    protected function getLiveBaseUrl(): string
    {
        return 'https://app.vepara.com.tr/ccpayment';
    }
}
