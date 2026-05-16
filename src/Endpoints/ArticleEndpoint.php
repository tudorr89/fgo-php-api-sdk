<?php

declare(strict_types=1);

namespace FgoApi\Endpoints;

use FgoApi\Client;
use FgoApi\Hash;
use FgoApi\Types\Article;
use FgoApi\Types\ArticleListResult;

final class ArticleEndpoint
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    /**
     * List all articles with pagination.
     *
     * ENTERPRISE plan only.
     */
    public function list(int $page = 1, int $perPage = 50): ArticleListResult
    {
        $response = $this->client->post('articol/list', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $this->baseHash(),
            'NrPagina' => \max(1, $page),
            'NrArticole' => \max(1, \min($perPage, 200)),
        ]);

        return ArticleListResult::fromArray($response);
    }

    /**
     * Get a single article by its account code.
     */
    public function get(string $articleCode): Article
    {
        $response = $this->client->post('articol/get', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $this->baseHash(),
            'CodArticol' => $articleCode,
        ]);

        if (!isset($response['Result']) || !\is_array($response['Result'])) {
            throw new \FgoApi\Exceptions\FgoApiException('API response did not contain a Result object.');
        }

        return Article::fromArray($response['Result']);
    }

    /**
     * Get multiple articles by their account codes (max 30).
     *
     * @deprecated Use modifiedArticles() instead.
     * @param  string[] $codes
     * @return array<Article>
     */
    public function getList(array $codes): array
    {
        $response = $this->client->post('articol/getlist', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $this->baseHash(),
            'CodArticol' => \implode(',', \array_slice($codes, 0, 30)),
        ]);

        $articles = [];
        if (isset($response['Result']) && \is_array($response['Result'])) {
            foreach ($response['Result'] as $article) {
                if (\is_array($article)) {
                    $articles[] = Article::fromArray($article);
                }
            }
        }

        return $articles;
    }

    /**
     * Get articles modified within a time window.
     *
     * ENTERPRISE plan only.
     *
     * @return array<Article>
     */
    public function modifiedArticles(int $hoursBack = 24, ?int $hoursTo = null): array
    {
        $payload = [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $this->baseHash(),
            'NumarOre' => \max(1, \min($hoursBack, 170)),
        ];

        if ($hoursTo !== null) {
            $payload['NumarOrePanaLa'] = $hoursTo;
        }

        $response = $this->client->post('articol/articolemodificate', $payload);

        $articles = [];
        if (isset($response['Result']) && \is_array($response['Result'])) {
            foreach ($response['Result'] as $article) {
                if (\is_array($article)) {
                    $articles[] = Article::fromArray($article);
                }
            }
        }

        return $articles;
    }

    private function baseHash(): string
    {
        return Hash::forBase(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
        );
    }
}
