<?php

declare(strict_types=1);

namespace FgoApi\Types;

readonly class Warehouse
{
    public function __construct(
        public string $code,
        public string $name,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['CodGestiune'],
            name: $data['Nume'],
        );
    }
}
