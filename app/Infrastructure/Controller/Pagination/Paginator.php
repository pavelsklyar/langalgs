<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Pagination;

use GuzzleHttp\Psr7\Uri;
use League\Fractal\Pagination\PaginatorInterface;

/**
 * Пагинатор для списков.
 */
final class Paginator implements PaginatorInterface
{
    private Pageable $pageable;
    private int $total;
    private int $totalPages;

    public function __construct(Pageable $pageable, int $total)
    {
        $this->pageable = $pageable;
        $this->total = $total;
        $this->totalPages = (int) ceil($this->total / $this->getPerPage());
    }

    public function getCount(): int
    {
        if ($this->totalPages < $this->getCurrentPage()) {
            return 0;
        }

        $perPage = $this->getPerPage();

        if ($this->totalPages > $this->getCurrentPage()) {
            return $perPage;
        }

        $lastPageCount = $this->total % $perPage;

        // На последней странице желаемое количество записей. 40 % 10 === 0
        if ($lastPageCount === 0) {
            return $perPage;
        }

        return $lastPageCount;
    }

    public function getLastPage(): int
    {
        $lastPage = (int) ceil($this->total / $this->getPerPage());

        return $lastPage > 0 ? $lastPage : 1;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPerPage(): int
    {
        return $this->pageable->getPerPage();
    }

    public function getCurrentPage(): int
    {
        return $this->pageable->getPage();
    }

    public function getUrl(int $page): string
    {
        $uri = new Uri($this->pageable->getUrl());

        return (string) Uri::withQueryValues(
            $uri,
            [
                'perPage' => (string) $this->getPerPage(),
                'page' => (string) $page,
            ]
        );
    }
}
