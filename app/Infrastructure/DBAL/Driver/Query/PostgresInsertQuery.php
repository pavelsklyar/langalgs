<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Infrastructure\DBAL\Driver\Query;

use App\Infrastructure\DBAL\Driver\LaravelPostgresConnectionDriver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Exception\ReadonlyConnectionException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Query\ReturningInterface;
use Throwable;

/**
 * Postgres driver requires little bit different way to handle last insert id.
 */
class PostgresInsertQuery extends InsertQuery implements ReturningInterface
{
    /** @var LaravelPostgresConnectionDriver|null */
    protected ?DriverInterface $driver = null;

    protected ?string $returning = null;

    /**
     * Set returning column. If not set, the driver will detect PK automatically.
     */
    public function returning(string|FragmentInterface ...$columns): self
    {
        if ($columns === []) {
            throw new BuilderException('RETURNING clause should contain at least 1 column.');
        }

        if (\count($columns) > 1) {
            throw new BuilderException(
                'Postgres driver supports only single column returning at this moment.'
            );
        }

        // @phpstan-ignore-next-line
        $this->returning = (string) $columns[0];

        return $this;
    }

    public function run(): mixed
    {
        $params = new QueryParameters();

        /** @var DriverInterface $driver */
        $driver = $this->driver;
        $queryString = $this->sqlStatement($params);

        if ($driver->isReadonly()) {
            throw ReadonlyConnectionException::onWriteStatementExecution();
        }

        $result = $driver->query($queryString, $params->getParameters());

        try {
            if ($this->getPrimaryKey() !== null) {
                /** @var int|non-empty-string|null $fetch */
                $fetch = $result->fetchColumn();

                return $fetch;
            }

            return null;
        } finally {
            $result->close();
        }
    }

    public function getTokens(): array
    {
        return [
            'table' => $this->table,
            'return' => $this->getPrimaryKey(),
            'columns' => $this->columns,
            'values' => $this->values,
        ];
    }

    private function getPrimaryKey(): ?string
    {
        $primaryKey = $this->returning;

        if ($primaryKey === null && $this->driver !== null && $this->prefix !== null) {
            try {
                $primaryKey = $this->driver->getPrimaryKey($this->prefix, $this->table);
            } catch (Throwable) {
                return null;
            }
        }

        return $primaryKey;
    }
}
