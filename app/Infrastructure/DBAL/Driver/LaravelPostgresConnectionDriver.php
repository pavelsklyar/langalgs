<?php

declare(strict_types=1);

namespace App\Infrastructure\DBAL\Driver;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Driver\Postgres\PostgresCompiler;
use Cycle\Database\Driver\Postgres\PostgresHandler;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Query\BuilderInterface;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

class LaravelPostgresConnectionDriver extends LaravelConnectionDriver
{
    /**
     * Cached list of primary keys associated with their table names. Used by InsertBuilder to
     * emulate last insert id.
     */
    private array $primaryKeys = [];

    public function __construct(
        HandlerInterface $schemaHandler,
        BuilderInterface $queryBuilder,
        CompilerInterface $queryCompiler,
        ConnectionInterface $connection,
        DateTimeZone $timeZone,
    ) {
        if (!$schemaHandler instanceof PostgresHandler) {
            throw new InvalidArgumentException();
        }

        if (!$queryCompiler instanceof PostgresCompiler) {
            throw new InvalidArgumentException();
        }

        parent::__construct($schemaHandler, $queryBuilder, $queryCompiler, $connection, $timeZone);
    }

    /**
     * Get singular primary key associated with desired table. Used to emulate last insert id.
     *
     * @param string $prefix Database prefix if any
     * @param string $table Fully specified table name, including postfix
     *
     * @throws DriverException
     */
    public function getPrimaryKey(string $prefix, string $table): ?string
    {
        $name = $prefix . $table;
        if (isset($this->primaryKeys[$name])) {
            return $this->primaryKeys[$name];
        }

        if (!$this->getSchemaHandler()->hasTable($name)) {
            throw new DriverException(
                "Unable to fetch table primary key, no such table '{$name}' exists"
            );
        }

        $this->primaryKeys[$name] = $this->getSchemaHandler()
            ->getSchema($table, $prefix)
            ->getPrimaryKeys();

        if (\count($this->primaryKeys[$name]) === 1) {
            // We do support only single primary key
            $this->primaryKeys[$name] = $this->primaryKeys[$name][0];
        } else {
            $this->primaryKeys[$name] = null;
        }

        return $this->primaryKeys[$name];
    }

    /**
     * Reset primary keys cache.
     */
    public function resetPrimaryKeys(): void
    {
        $this->primaryKeys = [];
    }
}
