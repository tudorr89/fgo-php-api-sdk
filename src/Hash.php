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

    /**
     * Hash for endpoints that only require CodUnic + PrivateKey
     * (articles, nomenclatures, warehouses).
     */
    public static function forBase(string $codUnic, string $privateKey): string
    {
        return \strtoupper(\sha1($codUnic . $privateKey));
    }

    /**
     * @deprecated Use {@see self::forBase()} — same hash, clearer name.
     */
    public static function forArticle(string $codUnic, string $privateKey): string
    {
        return self::forBase($codUnic, $privateKey);
    }
}
