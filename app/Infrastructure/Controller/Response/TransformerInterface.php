<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Response;

/**
 * Трансформер данных.
 *
 * @template T of object
 */
interface TransformerInterface
{
    /**
     * Преобразование объекта в массив для дальнейшней сериализации.
     *
     * @param T $object
     */
    public function transform(object $object): array;

    /**
     * Тип ресурса.
     *
     * @return non-empty-string
     */
    public function getType(): string;
}
