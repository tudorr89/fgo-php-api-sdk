# FGO API PHP Client

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Packagist](https://img.shields.io/badge/packagist-tudorr89%2Ffgo--php--api--sdk-orange.svg)](https://packagist.org/packages/tudorr89/fgo-php-api-sdk)

A fully-typed PHP client for the [FGO Invoicing/Billing API v7.0](https://api-testuat.fgo.ro/v1/testing.html).  
Create and manage invoices, query nomenclatures, list articles, and more — with clean DTOs and Guzzle under the hood.

---

## Requirements

- PHP 8.1 or higher
- [Composer](https://getcomposer.org/)
- A [FGO](https://fgo.ro) account with an API user (Private Key)

---

## Installation

```bash
composer require tudorr89/fgo-php-api-sdk
```

---

## Quick Start

```php
use FgoApi\Client;
use FgoApi\Enums\Environment;
use FgoApi\Types\AddressClient;
use FgoApi\Types\InvoiceLine;

$client = new Client(
    codUnic:     'YOUR_CUI',
    privateKey:  'YOUR_PRIVATE_KEY',
    platformUrl: 'https://your-app.com',
    environment: Environment::Test,
);

// Create an invoice
$invoice = $client->invoices()->create(
    series:      'BV',
    currency:    'RON',
    invoiceType: 'Factura',
    clientData:  new AddressClient(
        name:    'Ionescu Popescu',
        country: 'RO',
        county:  'Bucuresti',
        type:    'PF',
    ),
    lines: [
        new InvoiceLine(
            name:      'Servicii Consultanta',
            quantity:   2,
            unit:      'ORE',
            vatRate:   19,
            unitPrice: 150.00,
        ),
    ],
);

echo "Invoice: {$invoice->series}{$invoice->number}\n";
echo "PDF: {$invoice->pdfLink}\n";
```

---

## Authentication

All requests are signed with an SHA-1 hash. The `Hash` helper provides the correct calculation for each endpoint category:

```php
use FgoApi\Hash;

// Invoice creation — includes client name
Hash::forInvoiceCreate('CUI', 'PRIVATE_KEY', 'Client Name');

// Invoice operations (print, cancel, etc.) — includes invoice number
Hash::forInvoiceOperation('CUI', 'PRIVATE_KEY', '001');

// Articles, nomenclatures, warehouses — no extra data
Hash::forArticle('CUI', 'PRIVATE_KEY');
```

The `Client` handles hashing automatically — you never need to call these directly.

---

## API Reference

### Invoices

| Method | Endpoint | Description |
|---|---|---|
| `invoices()->create(...)` | `POST /factura/emitere` | Create and emit a new invoice |
| `invoices()->print($num, $serie)` | `POST /factura/print` | Generate PDF download link |
| `invoices()->getStatus($num, $serie)` | `POST /factura/getstatus` | Get invoice value and paid amount |
| `invoices()->cancel($num, $serie)` | `POST /factura/anulare` | Cancel (keeps in history) |
| `invoices()->delete($num, $serie)` | `POST /factura/stergere` | Permanently delete |
| `invoices()->reverse($num, $serie)` | `POST /factura/stornare` | Reverse / credit note |
| `invoices()->addPayment(...)` | `POST /factura/incasare` | Record a payment (Premium+) |
| `invoices()->deletePayment($num, $serie)` | `POST /factura/stergereincasare` | Delete a payment |
| `invoices()->addTrackingNumber(...)` | `POST /factura/awb` | Attach courier AWB |
| `invoices()->listAssociated($num, $serie)` | `POST /factura/listfacturiasociate` | List linked invoices (Enterprise) |

```php
// Full create example
$result = $client->invoices()->create(
    series:          'BV',
    currency:        'RON',
    invoiceType:     'Factura',
    clientData:      new AddressClient(
        name:       'SC Example SRL',
        fiscalCode: 'RO12345678',
        email:      'contact@example.com',
        phone:      '0712345678',
        country:    'RO',
        county:     'Cluj',
        locality:   'Cluj-Napoca',
        address:    'Str. Principala, Nr. 10',
        type:       'PJ',
    ),
    lines: [
        new InvoiceLine(
            name:        'Dezvoltare Software',
            quantity:    1,
            unit:        'BUC',
            vatRate:     19,
            unitPrice:   5000.00,
            description: 'Modul facturare — luna aprilie',
        ),
    ],
    number:          null,
    issueDate:       date('Y-m-d'),
    dueDate:         null,
    checkDuplicate:  false,
    vatOnCollection: false,
);

// $result->number, $result->series, $result->pdfLink, $result->paymentLink, $result->stockInfo[]
```

### Nomenclatures

| Method | Returns |
|---|---|
| `nomenclatures()->countries()` | All countries |
| `nomenclatures()->counties()` | All counties |
| `nomenclatures()->vatRates()` | VAT rates |
| `nomenclatures()->banks()` | Banks |
| `nomenclatures()->paymentTypes()` | Payment types |
| `nomenclatures()->invoiceTypes()` | Invoice types |
| `nomenclatures()->clientTypes()` | Client types (PF/PJ) |
| `nomenclatures()->localities('Bucuresti')` | Localities by county code |

```php
$types = $client->nomenclatures()->invoiceTypes();
// [ { name: "Normal", value: "Factura" }, { name: "Simplified", value: "FacturaSimplificata" } ]
```

### Articles

| Method | Description |
|---|---|
| `articles()->list($page, $perPage)` | Paginated article list (Enterprise) |
| `articles()->get($code)` | Single article by account code |
| `articles()->getList(array $codes)` | Multiple articles, max 30 — **deprecated** |
| `articles()->modifiedArticles($hoursBack, $hoursTo)` | Articles modified in time window (Enterprise) |

```php
$result = $client->articles()->list(page: 1, perPage: 50);
// $result->total, $result->articles[] — each Article has name, unitPrice, stock, barcode, etc.
```

### Warehouses

```php
$warehouses = $client->warehouses()->list();
// { code: "WH001", name: "Main Warehouse" }
```

---

## Environments

```php
use FgoApi\Enums\Environment;

// Test (UAT)
new Client(..., environment: Environment::Test);

// Production
new Client(..., environment: Environment::Production);

// Custom URL
new Client(..., environment: 'https://custom-fgo.example.com/v1');
```

---

## Exception Handling

All exceptions extend `FgoApi\Exceptions\FgoApiException`:

| Exception | Trigger |
|---|---|
| `FgoApiException` | Generic API error (non-success response) |
| `AuthenticationException` | HTTP 401 — invalid credentials |
| `RateLimitException` | HTTP 429 — rate limit hit |
| `NotFoundException` | HTTP 404 — resource not found |
| `HttpException` | Other HTTP errors (includes status code + body) |
| `ValidationException` | Validation errors |

```php
try {
    $invoice = $client->invoices()->create(...);
} catch (ValidationException $e) {
    // 400 / `Errors` map from API
    print_r($e->getErrors());
} catch (AuthenticationException $e) {
    // 401 / 403 — wrong CUI or private key
} catch (RateLimitException $e) {
    sleep(max(1, $e->getRetryAfter()));
    // ...retry
} catch (FgoApiException $e) {
    // All other API errors
}
```

---

## Rate Limits

The API enforces per-endpoint rate limits. The client does **not** automatically retry — implement your own retry logic as needed:

| Endpoint | Limit |
|---|---|
| Invoice create / payment | 1 req/sec, 15s timeout |
| Articles | 1 req/5 sec |
| Standard endpoints | No explicit limit |

---

## Development

```bash
git clone https://github.com/tudorr89/fgo-php-api-sdk
cd fgo-php-api-sdk
composer install

# Static analysis
composer analyse

# Run tests
composer test
```

---

## License

MIT. See [LICENSE](LICENSE).

## Resources

- [FGO API Documentation](https://api-testuat.fgo.ro/v1/testing.html)
- [FGO Registration (Test)](https://testuat.fgo.ro/inregistrare)
- [FGO Registration (Production)](https://www.fgo.ro/inregistrare)
