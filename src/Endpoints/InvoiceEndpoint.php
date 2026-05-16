<?php

declare(strict_types=1);

namespace FgoApi\Endpoints;

use FgoApi\Client;
use FgoApi\Hash;
use FgoApi\Types\InvoiceResult;
use FgoApi\Types\InvoiceStatusResult;
use FgoApi\Types\AddressClient;
use FgoApi\Types\InvoiceLine;
use FgoApi\Types\AssociatedInvoice;

final class InvoiceEndpoint
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    /**
     * Create and emit a new invoice.
     *
     * @param  string               $series       Invoice series from FGO Settings
     * @param  string               $currency     Currency code (e.g. RON, EUR)
     * @param  string               $invoiceType  Invoice type from nomenclature
     * @param  AddressClient        $clientData   Client details
     * @param  array<InvoiceLine>   $lines        Invoice content lines
     * @param  string|null          $number       Invoice number (auto-generated if omitted)
     * @param  string|null          $issueDate    Issue date (yyyy-mm-dd)
     * @param  string|null          $dueDate      Due date
     * @param  bool                 $checkDuplicate Whether to check for duplicates
     * @param  bool                 $vatOnCollection VAT at collection
     * @return InvoiceResult
     */
    public function create(
        string $series,
        string $currency,
        string $invoiceType,
        AddressClient $clientData,
        array $lines,
        ?string $number = null,
        ?string $issueDate = null,
        ?string $dueDate = null,
        bool $checkDuplicate = false,
        bool $vatOnCollection = false,
    ): InvoiceResult {
        $hash = Hash::forInvoiceCreate(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
            $clientData->name,
        );

        $payload = [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'Serie' => $series,
            'Valuta' => $currency,
            'TipFactura' => $invoiceType,
            'Client' => $clientData->toArray(),
            'Continut' => \array_map(static fn(InvoiceLine $line) => $line->toArray(), $lines),
            'VerificareDuplicat' => $checkDuplicate,
        ];

        if ($number !== null) {
            $payload['Numar'] = $number;
        }
        if ($issueDate !== null) {
            $payload['DataEmitere'] = $issueDate;
        }
        if ($dueDate !== null) {
            $payload['DataScadenta'] = $dueDate;
        }
        if ($vatOnCollection) {
            $payload['TvaLaIncasare'] = true;
        }

        $response = $this->client->post('factura/emitere', $payload);

        return InvoiceResult::fromArray($response['Factura']);
    }

    /**
     * Generate PDF for an existing invoice.
     */
    public function print(string $number, string $series): InvoiceResult
    {
        $hash = Hash::forInvoiceOperation(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
            $number,
        );

        $response = $this->client->post('factura/print', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'Numar' => $number,
            'Serie' => $series,
        ]);

        return InvoiceResult::fromArray($response['Factura']);
    }

    /**
     * Permanently delete an invoice.
     */
    public function delete(string $number, string $series): void
    {
        $hash = Hash::forInvoiceOperation(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
            $number,
        );

        $this->client->post('factura/stergere', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'Numar' => $number,
            'Serie' => $series,
        ]);
    }

    /**
     * Cancel an invoice (keeps it in history).
     */
    public function cancel(string $number, string $series): void
    {
        $hash = Hash::forInvoiceOperation(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
            $number,
        );

        $this->client->post('factura/anulare', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'Numar' => $number,
            'Serie' => $series,
        ]);
    }

    /**
     * Get current status of an invoice.
     */
    public function getStatus(string $number, string $series): InvoiceStatusResult
    {
        $hash = Hash::forInvoiceOperation(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
            $number,
        );

        $response = $this->client->post('factura/getstatus', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'Numar' => $number,
            'Serie' => $series,
        ]);

        return InvoiceStatusResult::fromArray($response['Factura']);
    }

    /**
     * Record a payment for an invoice.
     *
     * WARNING: Decommissioned for FGO Pro. Premium & Enterprise only.
     */
    public function addPayment(
        string $invoiceNumber,
        string $invoiceSeries,
        string $paymentType,
        float $amount,
        string $paymentDate,
    ): void {
        $hash = Hash::forInvoiceOperation(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
            $invoiceNumber,
        );

        $this->client->post('factura/incasare', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'NumarFactura' => $invoiceNumber,
            'SerieFactura' => $invoiceSeries,
            'TipIncasare' => $paymentType,
            'SumaIncasata' => $amount,
            'DataIncasare' => $paymentDate,
        ]);
    }

    /**
     * Delete a payment from an invoice.
     */
    public function deletePayment(string $invoiceNumber, string $invoiceSeries): void
    {
        $hash = Hash::forInvoiceOperation(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
            $invoiceNumber,
        );

        $this->client->post('factura/stergereincasare', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'Numar' => $invoiceNumber,
            'Serie' => $invoiceSeries,
        ]);
    }

    /**
     * Reverse an invoice (create credit note).
     */
    public function reverse(
        string $number,
        string $series,
        ?string $stornoSeries = null,
        ?string $stornoNumber = null,
        ?string $issueDate = null,
    ): void {
        $hash = Hash::forInvoiceOperation(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
            $number,
        );

        $payload = [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'Numar' => $number,
            'Serie' => $series,
        ];

        if ($stornoSeries !== null) {
            $payload['SerieStorno'] = $stornoSeries;
        }
        if ($stornoNumber !== null) {
            $payload['NumarStorno'] = $stornoNumber;
        }
        if ($issueDate !== null) {
            $payload['DataEmitere'] = $issueDate;
        }

        $this->client->post('factura/stornare', $payload);
    }

    /**
     * Add a courier tracking number (AWB) to an invoice.
     */
    public function addTrackingNumber(string $number, string $series, string $awb): void
    {
        $hash = Hash::forInvoiceOperation(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
            $number,
        );

        $this->client->post('factura/awb', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'Numar' => $number,
            'Serie' => $series,
            'AWB' => $awb,
        ]);
    }

    /**
     * List all invoices associated with a given invoice.
     *
     * ENTERPRISE plan only.
     *
     * @return array<AssociatedInvoice>
     */
    public function listAssociated(string $number, string $series): array
    {
        $hash = Hash::forInvoiceOperation(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
            $number,
        );

        $response = $this->client->post('factura/listfacturiasociate', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'Numar' => $number,
            'Serie' => $series,
        ]);

        $invoices = [];
        if (isset($response['Facturi']) && \is_array($response['Facturi'])) {
            foreach ($response['Facturi'] as $inv) {
                $invoices[] = AssociatedInvoice::fromArray($inv);
            }
        }

        return $invoices;
    }
}
