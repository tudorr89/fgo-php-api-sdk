<?php

declare(strict_types=1);

namespace FgoApi\Tests;

use FgoApi\Enums\ClientType;
use FgoApi\Types\AddressClient;
use PHPUnit\Framework\TestCase;

final class AddressClientTest extends TestCase
{
    public function test_to_array_emits_only_required_fields_by_default(): void
    {
        $a = new AddressClient(name: 'Acme');

        self::assertSame(
            ['Denumire' => 'Acme', 'Tara' => 'RO', 'Tip' => 'PF'],
            $a->toArray(),
        );
    }

    public function test_accepts_client_type_enum(): void
    {
        $a = new AddressClient(name: 'Acme', type: ClientType::LegalEntity);

        self::assertSame('PJ', $a->type);
        self::assertSame('PJ', $a->toArray()['Tip']);
    }

    public function test_to_array_includes_optional_fields_when_set(): void
    {
        $a = new AddressClient(
            name: 'Acme',
            fiscalCode: 'RO123',
            email: 'a@b.c',
            phone: '0712',
            country: 'RO',
            county: 'Cluj',
            locality: 'Cluj-Napoca',
            address: 'Str. 1',
            type: ClientType::LegalEntity,
            externalId: 'ext-1',
            isForeign: false,
        );

        $arr = $a->toArray();

        self::assertSame('RO123', $arr['CodUnic']);
        self::assertSame('a@b.c', $arr['Email']);
        self::assertSame('0712', $arr['Telefon']);
        self::assertSame('Cluj', $arr['Judet']);
        self::assertSame('Cluj-Napoca', $arr['Localitate']);
        self::assertSame('Str. 1', $arr['Adresa']);
        self::assertSame('ext-1', $arr['IdExtern']);
        self::assertFalse($arr['Strain']);
    }
}
