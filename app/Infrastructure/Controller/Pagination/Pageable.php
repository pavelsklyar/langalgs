<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Pagination;

/**
 * Пагинация.
 */
final class Pageable
{
    /**
     * @param positive-int $perPage
     * @param positive-int $page
     * @param non-empty-string $url
     */
    public function __construct(
        private readonly int $perPage,
        private readonly int $page,
        private readonly string $url
    ) {
    }

    /**
     * @return positive-int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @return positive-int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return int<0, max>
     */
    public function getOffset(): int
    {
        /** @psalm-var int<0, max> $pageOffset */
        $pageOffset = $this->page - 1;

        return $pageOffset * $this->perPage;
    }

    /**
     * @return non-empty-string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
