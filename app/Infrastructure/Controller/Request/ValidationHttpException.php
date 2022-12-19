<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Request;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationHttpException extends BadRequestHttpException
{
    private readonly ConstraintViolationListInterface $violations;

    public function __construct(ConstraintViolationListInterface $violations)
    {
        parent::__construct('Ошибка валидации данных');

        $this->violations = $violations;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
