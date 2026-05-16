<?php

declare(strict_types=1);

namespace FgoApi\Tests;

use FgoApi\Hash;
use PHPUnit\Framework\TestCase;

final class HashTest extends TestCase
{
    public function test_invoice_create_hash_is_uppercase_sha1_of_concatenation(): void
    {
        $expected = \strtoupper(\sha1('CUI123' . 'PRIVKEY' . 'Acme SRL'));

        self::assertSame($expected, Hash::forInvoiceCreate('CUI123', 'PRIVKEY', 'Acme SRL'));
    }

    public function test_invoice_operation_hash_uses_invoice_number(): void
    {
        $expected = \strtoupper(\sha1('CUI123' . 'PRIVKEY' . '001'));

        self::assertSame($expected, Hash::forInvoiceOperation('CUI123', 'PRIVKEY', '001'));
    }

    public function test_base_hash_is_uppercase_sha1_of_cui_and_key(): void
    {
        $expected = \strtoupper(\sha1('CUI123' . 'PRIVKEY'));

        self::assertSame($expected, Hash::forBase('CUI123', 'PRIVKEY'));
    }

    public function test_legacy_for_article_alias_matches_for_base(): void
    {
        self::assertSame(
            Hash::forBase('CUI123', 'PRIVKEY'),
            Hash::forArticle('CUI123', 'PRIVKEY'),
        );
    }
}
