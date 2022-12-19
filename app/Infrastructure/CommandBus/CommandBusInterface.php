<?php

declare(strict_types=1);

namespace App\Infrastructure\CommandBus;

/**
 * Шина команд.
 */
interface CommandBusInterface
{
    public function handle(object $command): void;
}
