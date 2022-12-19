<?php

declare(strict_types=1);

namespace App\Infrastructure\CommandBus;

use App\Infrastructure\DBAL\Flusher\Flusher;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Шина команд.
 */
final class CommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly ValidatorInterface $validator,
        private readonly Flusher $flusher,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(object $command): void
    {
        $violations = $this->validator->validate($command);

        if ($violations->count() > 0) {
            throw new CommandValidationFailedException($command::class, $violations);
        }

        $this->handleCommand($command);

        $this->flusher->flush();
    }

    private function handleCommand(object $command): void
    {
        $commandClass = $command::class;
        $handlerClass = sprintf('%sHandler', $commandClass);

        $handler = $this->container->make($handlerClass);

        if (!\is_callable($handler)) {
            throw new InvalidArgumentException(
                sprintf('Обработчик для команды %s должен иметь метод __invoke()', $commandClass)
            );
        }

        $this->logger->info(
            sprintf('Запускается обработка команды %s', $command::class),
            [
                'func_name' => __METHOD__,
            ]
        );

        $handler($command);

        $this->logger->info(
            sprintf('Команда %s успешно обработана', $command::class),
            [
                'func_name' => __METHOD__,
            ]
        );
    }
}
