<?php

declare(strict_types=1);

namespace App\Infrastructure\DBAL;

use App\Infrastructure\DBAL\Connection\WrappedLoggedConnection;
use App\Infrastructure\DBAL\Driver\LaravelPostgresConnectionDriver;
use App\Infrastructure\DBAL\Driver\Query\PostgresInsertQuery;
use App\Infrastructure\DBAL\Schema\ORMSchema;
use Closure;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\Postgres\PostgresCompiler;
use Cycle\Database\Driver\Postgres\PostgresHandler;
use Cycle\Database\Driver\Postgres\Query\PostgresSelectQuery;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\QueryBuilder;
use Cycle\Database\Query\UpdateQuery;
use Cycle\ORM\EntityManager;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Factory as ORMFactory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use DateTimeZone;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use PDO;

final class DBALServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Connection::resolverFor(
            'pgsql',
            static function (PDO|Closure $connection, string $database, string $prefix, array $config) {
                return new WrappedLoggedConnection(
                    Log::channel('database'),
                    $connection,
                    $database,
                    $prefix,
                    $config,
                );
            }
        );

        $this->app->singleton(
            'app.database.manager',
            static function (Container $container): DatabaseManager {
                /** @var PostgresSelectQuery $selectQuery */
                $selectQuery = $container->make(PostgresSelectQuery::class);

                /** @var PostgresInsertQuery $insertQuery */
                $insertQuery = $container->make(PostgresInsertQuery::class);

                /** @var UpdateQuery $updateQuery */
                $updateQuery = $container->make(UpdateQuery::class);

                /** @var DeleteQuery $deleteQuery */
                $deleteQuery = $container->make(DeleteQuery::class);

                /** @var ConnectionInterface $connection */
                $connection = $container->get(ConnectionInterface::class);

                /** @var string $appTimeZone */
                $appTimeZone = config('app.timezone');

                $database = new Database(
                    name: 'default',
                    prefix: '',
                    driver: new LaravelPostgresConnectionDriver(
                        schemaHandler: new PostgresHandler(),
                        queryBuilder: new QueryBuilder(
                            selectQuery: $selectQuery,
                            insertQuery: $insertQuery,
                            updateQuery: $updateQuery,
                            deleteQuery: $deleteQuery,
                        ),
                        queryCompiler: new PostgresCompiler('""'),
                        connection: $connection,
                        timeZone: new DateTimeZone($appTimeZone)
                    )
                );

                $manager = new DatabaseManager(
                    new DatabaseConfig()
                );

                $manager->addDatabase($database);

                return $manager;
            }
        );

        $this->app->singleton(
            ORMInterface::class,
            static function (Container $container): ORMInterface {
                /** @var DatabaseManager $databaseManager */
                $databaseManager = $container->get('app.database.manager');

                $schemas = [];

                /** @var ORMSchema $ormSchema */
                foreach ($container->tagged('app.database.mapping.schema') as $ormSchema) {
                    $schemas[$ormSchema->name] = $ormSchema->config;
                }

                return new ORM(
                    new ORMFactory($databaseManager),
                    new Schema($schemas),
                );
            }
        );

        $this->app->singleton(
            EntityManagerInterface::class,
            static function (Container $container): EntityManagerInterface {
                /** @var ORMInterface $orm */
                $orm = $container->get(ORMInterface::class);

                return new EntityManager($orm);
            }
        );

        $this->app->singleton(
            DatabaseInterface::class,
            static function (Container $container): DatabaseInterface {
                /** @var DatabaseManager $manager */
                $manager = $container->get('app.database.manager');

                return $manager->database();
            }
        );
    }
}
