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
use FgoApi\Exceptions\RateLimitException;
use FgoApi\Exceptions\ValidationException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;

final class Client
{
    public const VERSION = '1.0.3';

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
            'connect_timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'fgo-php-api-sdk/' . self::VERSION . ' (+https://github.com/tudorr89/fgo-php-api-sdk)',
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
                throw new FgoApiException('Invalid JSON response from API: ' . \substr($body, 0, 200));
            }

            if (!($result['Success'] ?? false)) {
                $message = $result['Message'] ?? $result['Error'] ?? 'Unknown API error';
                $errors = isset($result['Errors']) && \is_array($result['Errors']) ? $result['Errors'] : [];

                if ($errors !== []) {
                    throw new ValidationException((string) $message, $errors);
                }

                throw new FgoApiException((string) $message);
            }

            return $result;
        } catch (ConnectException $e) {
            throw new FgoApiException('Could not connect to FGO API: ' . $e->getMessage(), 0, $e);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode === 400) {
                throw new ValidationException('Validation error: ' . $body);
            }
            if ($statusCode === 401 || $statusCode === 403) {
                throw new AuthenticationException('Invalid credentials: ' . $body);
            }
            if ($statusCode === 404) {
                throw new NotFoundException('Resource not found: ' . $body);
            }
            if ($statusCode === 429) {
                $retryAfter = (int) ($response->getHeaderLine('Retry-After') ?: 0);
                throw new RateLimitException('Rate limit exceeded', $retryAfter);
            }

            throw new HttpException(
                message: "HTTP {$statusCode}: {$body}",
                statusCode: $statusCode,
                responseBody: $body,
            );
        } catch (ServerException $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

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
