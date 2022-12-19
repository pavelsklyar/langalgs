<?php

declare(strict_types=1);

namespace App\Infrastructure\CommandBus;

use App\Infrastructure\DBAL\Flusher\Flusher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CommandBusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            CommandBusInterface::class,
            static function (Container $container): CommandBus {
                /** @var ValidatorInterface $validator */
                $validator = $container->make(ValidatorInterface::class);

                /** @var Flusher $flusher */
                $flusher = $container->make(Flusher::class);

                /** @var LoggerInterface $logger */
                $logger = $container->make(LoggerInterface::class);

                return new CommandBus(
                    container: $container,
                    validator: $validator,
                    flusher: $flusher,
                    logger: $logger,
                );
            }
        );
    }
}
