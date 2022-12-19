<?php

declare(strict_types=1);

namespace App\Infrastructure\DBAL\Driver;

use App\Infrastructure\DBAL\Connection\WrappedLoggedConnection;
use BackedEnum;
use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Config\PDOConnectionConfig;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Driver\Statement;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\Query\BuilderInterface;
use Cycle\Database\StatementInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Драйвер Cycle ORM для использования ларавелевского коннекта к базе.
 */
class LaravelConnectionDriver implements DriverInterface
{
    private readonly HandlerInterface $schemaHandler;
    private readonly BuilderInterface $queryBuilder;
    private readonly CompilerInterface $queryCompiler;
    private readonly ConnectionInterface $connection;
    private readonly DateTimeZone $timeZone;

    public function __construct(
        HandlerInterface $schemaHandler,
        BuilderInterface $queryBuilder,
        CompilerInterface $queryCompiler,
        ConnectionInterface $connection,
        DateTimeZone $timeZone,
    ) {
        $this->schemaHandler = $schemaHandler->withDriver($this);
        $this->queryBuilder = $queryBuilder->withDriver($this);
        $this->queryCompiler = $queryCompiler;
        $this->connection = $connection;
        $this->timeZone = $timeZone;
    }

    /**
     * @param DriverConfig<PDOConnectionConfig> $config
     */
    public static function create(DriverConfig $config): DriverInterface
    {
        throw new RuntimeException('Не реализовано.');
    }

    public function connect(): void
    {
        // ignored
    }

    public function disconnect(): void
    {
        // ignored
    }

    public function quote(mixed $value, int $type = PDO::PARAM_STR): string
    {
        if (!$this->connection instanceof WrappedLoggedConnection) {
            throw new RuntimeException();
        }

        if ($value instanceof BackedEnum) {
            $value = (string) $value->value;
        }

        if ($value instanceof DateTimeImmutable) {
            $value = $this->formatDatetime($value);
        }

        /**
         * @var string $value
         * @var non-empty-string $result
         */
        $result = $this->connection->getPdo()->quote($value, $type);

        return $result;
    }

    /**
     * @psalm-suppress InternalClass
     * @psalm-suppress InternalMethod
     */
    public function query(string $statement, array $parameters = []): StatementInterface
    {
        if (!$this->connection instanceof WrappedLoggedConnection) {
            throw new RuntimeException();
        }

        $bindings = $this->prepareParameters($parameters);
        $pdoStatement = $this->connection->getPdoStatement($statement, $bindings);

        return new Statement($pdoStatement);
    }

    public function execute(string $query, array $parameters = []): int
    {
        $bindings = $this->prepareParameters($parameters);

        return $this->connection->update($query, $bindings);
    }

    public function lastInsertID(string $sequence = null): void
    {
        // ignored
    }

    public function beginTransaction(string $isolationLevel = null): bool
    {
        $this->connection->beginTransaction();

        return true;
    }

    public function commitTransaction(): bool
    {
        $this->connection->commit();

        return true;
    }

    public function rollbackTransaction(): bool
    {
        $this->connection->rollBack();

        return true;
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function isReadonly(): bool
    {
        return false;
    }

    public function getTransactionLevel(): int
    {
        return $this->connection->transactionLevel();
    }

    public function getType(): string
    {
        return 'laravel';
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timeZone;
    }

    public function getSchemaHandler(): HandlerInterface
    {
        return $this->schemaHandler;
    }

    public function getQueryCompiler(): CompilerInterface
    {
        return $this->queryCompiler;
    }

    public function getQueryBuilder(): BuilderInterface
    {
        return $this->queryBuilder;
    }

    private function formatDatetime(DateTimeInterface $value): string
    {
        try {
            $datetime = new DateTimeImmutable('now', $this->getTimezone());
        } catch (Throwable $exception) {
            throw new DriverException(
                $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }

        return $datetime
            ->setTimestamp($value->getTimestamp())
            ->format('Y-m-d H:i:s');
    }

    private function prepareParameters(iterable $parameters): array
    {
        $result = [];

        foreach ($parameters as $name => $parameter) {
            if ($parameter instanceof ParameterInterface) {
                $parameter = $parameter->getValue();
            }

            if ($parameter instanceof BackedEnum) {
                $parameter = $parameter->value;
            } elseif ($parameter instanceof DateTimeInterface) {
                $parameter = $this->formatDatetime($parameter);
            }

            $result[$name] = $parameter;
        }

        return $result;
    }
}
