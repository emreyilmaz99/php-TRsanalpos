<?php

namespace Emreyilmaz99\SanalPos\Enums;

enum InstallmentCommissionPolicy: int
{
    case Default = 0;
    case ChargeToCustomer = 1;
    case AbsorbByMerchant = 2;
}
