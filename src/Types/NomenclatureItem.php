<?php

declare(strict_types=1);

namespace FgoApi\Types;

readonly class NomenclatureItem
{
    public function __construct(
        public string $name,
        public ?string $value,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $value = $data['Valoare'] ?? null;

        return new self(
            name: (string) ($data['Nume'] ?? ''),
            value: $value === null ? null : (string) $value,
        );
    }
}
