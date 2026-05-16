<?php

declare(strict_types=1);

namespace FgoApi\Types;

use FgoApi\Enums\ClientType;

readonly class AddressClient
{
    public string $type;

    public function __construct(
        public string $name,
        public ?string $fiscalCode = null,
        public ?string $email = null,
        public ?string $phone = null,
        public string $country = 'RO',
        public ?string $county = null,
        public ?string $locality = null,
        public ?string $address = null,
        ClientType|string $type = ClientType::Individual,
        public ?string $externalId = null,
        public ?bool $isForeign = null,
    ) {
        $this->type = $type instanceof ClientType ? $type->value : $type;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'Denumire' => $this->name,
            'Tara' => $this->country,
            'Tip' => $this->type,
        ];

        if ($this->fiscalCode !== null) {
            $data['CodUnic'] = $this->fiscalCode;
        }
        if ($this->email !== null) {
            $data['Email'] = $this->email;
        }
        if ($this->phone !== null) {
            $data['Telefon'] = $this->phone;
        }
        if ($this->county !== null) {
            $data['Judet'] = $this->county;
        }
        if ($this->locality !== null) {
            $data['Localitate'] = $this->locality;
        }
        if ($this->address !== null) {
            $data['Adresa'] = $this->address;
        }
        if ($this->externalId !== null) {
            $data['IdExtern'] = $this->externalId;
        }
        if ($this->isForeign !== null) {
            $data['Strain'] = $this->isForeign;
        }

        return $data;
    }
}
