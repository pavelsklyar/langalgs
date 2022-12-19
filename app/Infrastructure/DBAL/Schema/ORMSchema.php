<?php

declare(strict_types=1);

namespace App\Infrastructure\DBAL\Schema;

/**
 * Схема для Cycle ORM.
 */
final class ORMSchema
{
    public function __construct(
        public readonly string $name,
        public readonly array $config,
    ) {
    }
}
