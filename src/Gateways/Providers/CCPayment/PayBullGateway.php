<?php

namespace Emreyilmaz99\SanalPos\Gateways\Providers\CCPayment;

class PayBullGateway extends AbstractCCPaymentGateway
{
    protected function getTestBaseUrl(): string
    {
        return 'https://test.paybull.com/ccpayment';
    }

    protected function getLiveBaseUrl(): string
    {
        return 'https://app.paybull.com/ccpayment';
    }
}
