<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Request;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

/**
 * Экстрактор данных запроса в DTO запроса.
 */
final class RequestDataExtractor
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $dataClass Класс DTO, в который замапятся данные
     *
     * @return T
     */
    public function extract(array $rawData, string $dataClass): object
    {
        try {
            return $this->deserializeRawData($rawData, $dataClass);
        } catch (ValidationHttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new BadRequestHttpException(
                $exception->getMessage(),
                $exception
            );
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $dataClass
     *
     * @return T
     */
    private function deserializeRawData(array $rawData, string $dataClass): object
    {
        $requestData = new $dataClass();
        $violations = $this->validateRawData($requestData, $rawData);

        if ($violations->count() > 0) {
            throw new ValidationHttpException($violations);
        }

        /**
         * @psalm-suppress MixedAssignment
         *
         * @var string $field
         */
        foreach ($rawData as $field => $value) {
            if (property_exists($requestData, $field)) {
                $requestData->{$field} = $value;
            }
        }

        return $requestData;
    }

    private function validateRawData(object $requestData, array $rawData): ConstraintViolationListInterface
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor($requestData);

        $fields = [];

        /** @psalm-suppress InternalProperty */
        foreach ($metadata->properties as $field => $propertyMetadata) {
            /** @var string $field */
            $fields[$field] = $propertyMetadata->getConstraints();
        }

        return $this->validator->validate(
            $rawData,
            new Collection(
                $fields,
                allowExtraFields: true,
            )
        );
    }
}
