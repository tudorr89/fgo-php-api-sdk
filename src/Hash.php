<?php

declare(strict_types=1);

namespace FgoApi;

final class Hash
{
    private function __construct() {}

    public static function forInvoiceCreate(string $codUnic, string $privateKey, string $clientName): string
    {
        return \strtoupper(\sha1($codUnic . $privateKey . $clientName));
    }

    public static function forInvoiceOperation(string $codUnic, string $privateKey, string $invoiceNumber): string
    {
        return \strtoupper(\sha1($codUnic . $privateKey . $invoiceNumber));
    }

    public static function forArticle(string $codUnic, string $privateKey): string
    {
        return \strtoupper(\sha1($codUnic . $privateKey));
    }
}
