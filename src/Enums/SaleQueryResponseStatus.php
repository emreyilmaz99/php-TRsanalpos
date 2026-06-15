<?php

namespace Emreyilmaz99\SanalPos\Enums;

enum SaleQueryResponseStatus: int
{
    case Error = 0;
    case Found = 1;
    case NotFound = 2;
}
