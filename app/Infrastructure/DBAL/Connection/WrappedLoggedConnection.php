<?php

declare(strict_types=1);

namespace App\Infrastructure\DBAL\Connection;

use Closure;
use Illuminate\Database\PostgresConnection;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

/**
 * Коннекшен, логирующий запросы к базе данных.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class WrappedLoggedConnection extends PostgresConnection
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        PDO|Closure $pdo,
        string $database = '',
        string $tablePrefix = '',
        array $config = []
    ) {
        $this->logger = $logger;

        parent::__construct($pdo, $database, $tablePrefix, $config);
    }

    public function statement($query, $bindings = []): bool
    {
        $this->logger->info(
            sprintf(
                '[DB] statement: %s, bindigs="%s"',
                $query,
                json_encode($bindings),
            ),
            [
                'func_name' => __METHOD__,
            ]
        );

        return parent::statement($query, $bindings);
    }

    public function commit(): void
    {
        $this->logger->info(
            '[DB] commit',
            [
                'func_name' => __METHOD__,
            ]
        );

        parent::commit();
    }

    public function rollBack($toLevel = null): void
    {
        $this->logger->info(
            '[DB] rollback',
            [
                'func_name' => __METHOD__,
            ]
        );

        parent::rollBack($toLevel);
    }

    public function getPdoStatement(string $query, array $bindings = []): PDOStatement
    {
        $statement = $this->getPdo()->prepare($query);

        $this->bindValues($statement, $bindings);
        $statement->execute();

        return $statement;
    }

    protected function createTransaction(): void
    {
        $this->logger->info(
            '[DB] begin transaction',
            [
                'func_name' => __METHOD__,
            ]
        );

        parent::createTransaction();
    }

    protected function createSavepoint(): void
    {
        $this->logger->info(
            '[DB] create savepoint',
            [
                'func_name' => __METHOD__,
            ]
        );

        parent::createSavepoint();
    }
}
