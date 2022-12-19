<?php

declare(strict_types=1);

namespace App\Infrastructure\CommandBus;

use RuntimeException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Ошибка валидации команды.
 */
final class CommandValidationFailedException extends RuntimeException
{
    private readonly ConstraintViolationListInterface $violations;

    public function __construct(string $commandClass, ConstraintViolationListInterface $violations)
    {
        parent::__construct(
            sprintf(
                'Валидация команды %s не пройдена: найдено %d ошибки.',
                $commandClass,
                $violations->count()
            )
        );

        $this->violations = $violations;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
