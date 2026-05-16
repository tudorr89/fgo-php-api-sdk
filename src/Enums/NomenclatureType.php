<?php

declare(strict_types=1);

namespace FgoApi\Enums;

enum NomenclatureType: string
{
    case Countries = 'tara';
    case Counties = 'judet';
    case VatRates = 'tva';
    case Banks = 'banca';
    case PaymentTypes = 'tipincasare';
    case InvoiceTypes = 'tipfactura';
    case ClientTypes = 'tipclient';
    case Localities = 'localitati';
}
