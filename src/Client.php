<?php

declare(strict_types=1);

namespace FgoApi;

use FgoApi\Endpoints\ArticleEndpoint;
use FgoApi\Endpoints\InvoiceEndpoint;
use FgoApi\Endpoints\NomenclatureEndpoint;
use FgoApi\Endpoints\WarehouseEndpoint;
use FgoApi\Enums\Environment;
use FgoApi\Exceptions\AuthenticationException;
use FgoApi\Exceptions\FgoApiException;
use FgoApi\Exceptions\HttpException;
use FgoApi\Exceptions\NotFoundException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

final class Client
{
    private HttpClient $http;
    private InvoiceEndpoint $invoice;
    private ArticleEndpoint $article;
    private NomenclatureEndpoint $nomenclature;
    private WarehouseEndpoint $warehouse;

    public function __construct(
        private readonly string $codUnic,
        private readonly string $privateKey,
        private readonly string $platformUrl,
        Environment|string $environment = Environment::Test,
        ?HttpClient $httpClient = null,
        private readonly int $timeout = 20,
    ) {
        $baseUrl = $environment instanceof Environment ? $environment->value : $environment;
        $this->http = $httpClient ?? new HttpClient([
            'base_uri' => \rtrim($baseUrl, '/') . '/',
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function invoices(): InvoiceEndpoint
    {
        return $this->invoice ??= new InvoiceEndpoint($this);
    }

    public function articles(): ArticleEndpoint
    {
        return $this->article ??= new ArticleEndpoint($this);
    }

    public function nomenclatures(): NomenclatureEndpoint
    {
        return $this->nomenclature ??= new NomenclatureEndpoint($this);
    }

    public function warehouses(): WarehouseEndpoint
    {
        return $this->warehouse ??= new WarehouseEndpoint($this);
    }

    public function getCodUnic(): string
    {
        return $this->codUnic;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getPlatformUrl(): string
    {
        return $this->platformUrl;
    }

    public function getHttpClient(): HttpClient
    {
        return $this->http;
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     *
     * @throws FgoApiException
     */
    public function post(string $endpoint, array $data): array
    {
        $data['PlatformaUrl'] = $data['PlatformaUrl'] ?? $this->platformUrl;

        try {
            $response = $this->http->post($endpoint, [
                'json' => $data,
            ]);

            $body = $response->getBody()->getContents();
            $result = \json_decode($body, true);

            if (!\is_array($result)) {
                throw new FgoApiException('Invalid JSON response from API');
            }

            if (!($result['Success'] ?? false)) {
                $message = $result['Message'] ?? $result['Error'] ?? 'Unknown API error';
                throw new FgoApiException($message);
            }

            return $result;
        } catch (ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $body = $e->getResponse()->getBody()->getContents();

            if ($statusCode === 401) {
                throw new AuthenticationException('Invalid credentials: ' . $body);
            }
            if ($statusCode === 404) {
                throw new NotFoundException('Resource not found: ' . $body);
            }
            if ($statusCode === 429) {
                throw new Exceptions\RateLimitException('Rate limit exceeded');
            }

            throw new HttpException(
                message: "HTTP {$statusCode}: {$body}",
                statusCode: $statusCode,
                responseBody: $body,
            );
        } catch (GuzzleException $e) {
            throw new FgoApiException('HTTP request failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
