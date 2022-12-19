<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Infrastructure\Controller\Request\HttpRequest;
use App\Infrastructure\Controller\Request\RequestDataExtractor;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use League\Fractal\Manager as FractalManager;
use League\Fractal\ScopeFactory as FractalScopeFactory;
use League\Fractal\Serializer\JsonApiSerializer;

final class ControllerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            FractalManager::class,
            static function (): FractalManager {
                $factory = new FractalScopeFactory();
                $manager = new FractalManager($factory);

                $serializer = new JsonApiSerializer();
                $manager->setSerializer($serializer);

                return $manager;
            }
        );
    }

    public function boot(): void
    {
        $this->app->resolving(HttpRequest::class, static function (Request $request, Container $app): void {
            /** @var Request $appRequest */
            $appRequest = $app['request'];
            $request = HttpRequest::createFrom($appRequest, $request);

            /** @var RequestDataExtractor $requestDataExtractor */
            $requestDataExtractor = $app->make(RequestDataExtractor::class);
            $request->setExtractor($requestDataExtractor);
        });
    }
}
