<?php

declare(strict_types=1);

namespace FgoApi\Types;

readonly class ArticleListResult
{
    /**
     * @param array<Article> $articles
     */
    public function __construct(
        public int $total,
        public int $page,
        public int $perPage,
        public array $articles,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $articles = [];
        if (isset($data['Result']['List']) && is_array($data['Result']['List'])) {
            foreach ($data['Result']['List'] as $article) {
                $articles[] = Article::fromArray($article);
            }
        }

        return new self(
            total: (int) $data['Result']['Total'],
            page: (int) $data['Result']['NrPagina'],
            perPage: (int) $data['Result']['NrArticole'],
            articles: $articles,
        );
    }
}
