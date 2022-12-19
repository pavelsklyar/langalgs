<?php

declare(strict_types=1);

namespace App\Infrastructure\DBAL\Flusher;

use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\ORMInterface;
use Throwable;

/**
 * Флашер изменений.
 */
class Flusher
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ORMInterface $orm,
        private int $depth = -1
    ) {
    }

    public function flush(): void
    {
        $this->depth++;

        try {
            if ($this->depth === 0) {
                $this->entityManager->run();

                $this->entityManager->clean();
                $this->orm->getHeap()->clean();
            }
        } catch (Throwable $exception) {
            $this->depth = -1;

            throw $exception;
        }
    }
}
