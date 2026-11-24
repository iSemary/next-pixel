<?php

namespace App\Enums;

enum CreditTransactionType: string
{
    case TopUp = 'topup';
    case Reserve = 'reserve';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
}
