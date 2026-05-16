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
        $hash = Hash::forArticle(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
        );

        $response = $this->client->post('articol/list', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'NrPagina' => $page,
            'NrArticole' => \min($perPage, 200),
        ]);

        return ArticleListResult::fromArray($response);
    }

    /**
     * Get a single article by its account code.
     */
    public function get(string $articleCode): Article
    {
        $hash = Hash::forArticle(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
        );

        $response = $this->client->post('articol/get', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'CodArticol' => $articleCode,
        ]);

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
        $hash = Hash::forArticle(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
        );

        $response = $this->client->post('articol/getlist', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'CodArticol' => \implode(',', \array_slice($codes, 0, 30)),
        ]);

        $articles = [];
        if (isset($response['Result']) && \is_array($response['Result'])) {
            foreach ($response['Result'] as $article) {
                $articles[] = Article::fromArray($article);
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
        $hash = Hash::forArticle(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
        );

        $payload = [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
            'NumarOre' => \min($hoursBack, 170),
        ];

        if ($hoursTo !== null) {
            $payload['NumarOrePanaLa'] = $hoursTo;
        }

        $response = $this->client->post('articol/articolemodificate', $payload);

        $articles = [];
        if (isset($response['Result']) && \is_array($response['Result'])) {
            foreach ($response['Result'] as $article) {
                $articles[] = Article::fromArray($article);
            }
        }

        return $articles;
    }
}
