<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Response;

use League\Fractal\Manager;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Фабрика HTTP-ответов.
 */
final class ResponseFactory
{
    public function __construct(
        private readonly Manager $fractal,
    ) {
    }

    /**
     * Создание ответа с ресурсом.
     *
     * @template T of object
     *
     * @param T $entity
     * @param TransformerAbstract&TransformerInterface<T> $transformer
     */
    public function createForEntity(
        object $entity,
        TransformerInterface $transformer,
        int $statusCode = Response::HTTP_OK
    ): Response {
        $item = new Item($entity, $transformer, $transformer->getType());

        /** @var array $data */
        $data = $this->fractal->createData($item)->toArray();

        return $this->createJsonResponse($statusCode, $data);
    }

    /**
     * Создание ответа с коллекцией ресурсов.
     *
     * @template T of object
     *
     * @param iterable<T> $list
     * @param TransformerAbstract&TransformerInterface<T> $transformer
     */
    public function createForEntityCollection(
        iterable $list,
        TransformerInterface $transformer,
    ): Response {
        $collection = new Collection($list, $transformer, $transformer->getType());

        /** @var array $data */
        $data = $this->fractal->createData($collection)->toArray();

        return $this->createJsonResponse(Response::HTTP_OK, $data);
    }

    /**
     * Создание ответа с коллекцией ресурсов и пагинацией.
     *
     * @template T of object
     *
     * @param iterable<T> $list
     * @param TransformerAbstract&TransformerInterface<T> $transformer
     */
    public function createForEntityCollectionWithPagination(
        iterable $list,
        TransformerInterface $transformer,
        PaginatorInterface $paginator,
    ): Response {
        $collection = new Collection($list, $transformer, $transformer->getType());
        $collection->setPaginator($paginator);

        /** @var array $data */
        $data = $this->fractal->createData($collection)->toArray();

        return $this->createJsonResponse(Response::HTTP_OK, $data);
    }

    /**
     * Создание ответа с кодом 200.
     */
    public function createOk(): Response
    {
        return $this->createJsonResponse(Response::HTTP_OK, []);
    }

    private function createJsonResponse(int $statusCode, array $data): Response
    {
        $body = json_encode($data);

        return new JsonResponse($body, $statusCode, [], true);
    }
}
