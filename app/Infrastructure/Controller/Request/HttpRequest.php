<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Request;

use Illuminate\Http\Request;

final class HttpRequest extends Request
{
    private RequestDataExtractor $extractor;

    /**
     * Получение отвалидированной структуры данных с данными запроса.
     *
     * @template T of object
     *
     * @param class-string<T> $dataClass
     *
     * @return T
     */
    public function extractData(string $dataClass): object
    {
        return $this->extractor->extract(
            array_merge(
                $this->query->all(),
                $this->request->all(),
                $this->files->all()
            ),
            $dataClass
        );
    }

    /**
     * Установка маппера.
     */
    public function setExtractor(RequestDataExtractor $extractor): self
    {
        $this->extractor = $extractor;

        return $this;
    }
}
