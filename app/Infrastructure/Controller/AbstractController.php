<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Illuminate\Http\JsonResponse;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController
{
    private ?FractalManager $fractalManager = null;

    /**
     * Получение ответа по спецификации json:api для одной сущности.
     *
     * @param object $entity Сущность
     * @param TransformerAbstract $transformer Трансформер данных
     * @param string $type Тип сущности, смотри спецификацию
     * @param int $statusCode HTTP код ответа
     */
    protected function createEntityResponse(
        object $entity,
        TransformerAbstract $transformer,
        string $type,
        int $statusCode = Response::HTTP_OK
    ): Response {
        $item = new Item($entity, $transformer, $type);

        /** @var array $data */
        $data = $this->getFractalManager()->createData($item)->toArray();

        return new JsonResponse($data, $statusCode);
    }

    /**
     * Получение ответа по спецификации json:api для коллекции сущностей.
     *
     * @param iterable $list Коллекция сущностей
     * @param TransformerAbstract $transformer Трансформер данных
     * @param string $type Тип сущности, смотри спецификацию
     * @param null|PaginatorInterface $paginator Пагинация
     * @param int $statusCode HTTP код ответа
     * @param array $meta Дополнительная сопровождающая мета-информация
     */
    protected function createEntityCollectionResponse(
        iterable $list,
        TransformerAbstract $transformer,
        string $type,
        ?PaginatorInterface $paginator = null,
        int $statusCode = Response::HTTP_OK,
        array $meta = []
    ): Response {
        $collection = new Collection($list, $transformer, $type);

        if ($paginator !== null) {
            $collection->setPaginator($paginator);
        }

        $collection->setMeta($meta);

        /** @var array $data */
        $data = $this->getFractalManager()->createData($collection)->toArray();

        return new JsonResponse($data, $statusCode);
    }

    /**
     * Получение ответа о ненайденной сущности.
     *
     * @param non-empty-string $message Текст ошибки
     */
    protected function createNotFoundResponse(string $message): JsonResponse
    {
        return new JsonResponse(
            ['message' => $message],
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Получение ответа о необработанной сущности.
     *
     * @param non-empty-string $code Код ошибки (uuid)
     * @param non-empty-string $message Текст ошибки
     */
    protected function createUnprocessableEntityResponse(string $code, string $message): Response
    {
        return new JsonResponse(
            [
                'errors' => [
                    [
                        [
                            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'code' => $code,
                            'title' => $message,
                        ],
                    ],
                ],
            ],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    protected function getFractalManager(): FractalManager
    {
        if ($this->fractalManager === null) {
            /** @var FractalManager $manager */
            $manager = app(FractalManager::class);
            $this->fractalManager = $manager;
        }

        return $this->fractalManager;
    }
}
