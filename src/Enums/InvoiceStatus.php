<?php

declare(strict_types=1);

namespace FgoApi\Enums;

enum InvoiceStatus: string
{
    case Emitted = 'Emisa';
    case Paid = 'Achitata';
    case Cancelled = 'Anulata';
    case Reversed = 'Stornata';
    case PartialPaid = 'PartialAchitata';
    case Deleted = 'Stearsa';
}
