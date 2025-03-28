<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Serializers\JsonApiSalonSerializer;
use Config;
use Flugg\Responder\Contracts\Pagination\PaginatorFactory;
use App\Services\CustomPaginatorFactory;
use Modules\Core\Contracts\RouterInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app['Dingo\Api\Exception\Handler']->setErrorFormat(array_merge(
            [
                'success' => false,
                'message' => ':message',
                'status_code' => ':status_code',
                'errors' => ':errors',
            ], $this->app->environment(['local']) ?
            [
                'code' => ':code',
                'debug' => ':debug'
            ] : []
        ));


        $this->app['Dingo\Api\Transformer\Factory']->setAdapter(function ($app) {
            $fractal = new \PHPOpenSourceSaver\Fractal\Manager();
            $fractal->setSerializer(new \PHPOpenSourceSaver\Fractal\Serializer\JsonApiSerializer());
            return new \Dingo\Api\Transformer\Adapter\Fractal($fractal, 'with', ',');
        });


        $this->app->singleton(PaginatorFactory::class, function ($app) {
            return new CustomPaginatorFactory($app['request']->toArray());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
