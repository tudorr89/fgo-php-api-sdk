<?php

declare(strict_types=1);

namespace FgoApi\Tests;

use FgoApi\Client;
use FgoApi\Enums\Environment;
use FgoApi\Exceptions\AuthenticationException;
use FgoApi\Exceptions\FgoApiException;
use FgoApi\Exceptions\NotFoundException;
use FgoApi\Exceptions\RateLimitException;
use FgoApi\Exceptions\ValidationException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ClientPostTest extends TestCase
{
    /**
     * @param  list<Response> $responses
     * @param  list<array{0: \Psr\Http\Message\RequestInterface, 1: array<string, mixed>}> $history Reference used to capture requests.
     */
    private function makeClient(array $responses, array &$history = []): Client
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $http = new HttpClient(['handler' => $stack, 'base_uri' => 'https://example.test/v1/']);

        return new Client(
            codUnic: 'CUI',
            privateKey: 'KEY',
            platformUrl: 'https://app.test',
            environment: Environment::Test,
            httpClient: $http,
        );
    }

    public function test_successful_response_returns_decoded_array(): void
    {
        $history = [];
        $client = $this->makeClient(
            [new Response(200, [], \json_encode(['Success' => true, 'Foo' => 'bar']))],
            $history,
        );

        $result = $client->post('foo/bar', ['X' => 1]);

        self::assertSame(['Success' => true, 'Foo' => 'bar'], $result);
        self::assertCount(1, $history);
        $body = \json_decode((string) $history[0]['request']->getBody(), true);
        self::assertSame('https://app.test', $body['PlatformaUrl']);
        self::assertSame(1, $body['X']);
    }

    public function test_unsuccessful_response_throws_fgo_exception_with_message(): void
    {
        $client = $this->makeClient([
            new Response(200, [], \json_encode(['Success' => false, 'Message' => 'Nope'])),
        ]);

        $this->expectException(FgoApiException::class);
        $this->expectExceptionMessage('Nope');

        $client->post('foo', []);
    }

    public function test_unsuccessful_response_with_errors_throws_validation_exception(): void
    {
        $client = $this->makeClient([
            new Response(200, [], \json_encode([
                'Success' => false,
                'Message' => 'Validation failed',
                'Errors' => ['Numar' => 'required'],
            ])),
        ]);

        try {
            $client->post('foo', []);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame('Validation failed', $e->getMessage());
            self::assertSame(['Numar' => 'required'], $e->getErrors());
        }
    }

    public function test_http_401_throws_authentication_exception(): void
    {
        $client = $this->makeClient([new Response(401, [], 'denied')]);

        $this->expectException(AuthenticationException::class);

        $client->post('foo', []);
    }

    public function test_http_404_throws_not_found_exception(): void
    {
        $client = $this->makeClient([new Response(404, [], 'missing')]);

        $this->expectException(NotFoundException::class);

        $client->post('foo', []);
    }

    public function test_http_429_throws_rate_limit_with_retry_after(): void
    {
        $client = $this->makeClient([new Response(429, ['Retry-After' => '12'], 'slow down')]);

        try {
            $client->post('foo', []);
            self::fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            self::assertSame(12, $e->getRetryAfter());
        }
    }

    public function test_invalid_json_throws_fgo_exception(): void
    {
        $client = $this->makeClient([new Response(200, [], 'not json')]);

        $this->expectException(FgoApiException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $client->post('foo', []);
    }
}
