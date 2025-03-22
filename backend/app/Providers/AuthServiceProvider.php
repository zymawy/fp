<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Dingo\Api\Auth\Provider\JWT;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Register your policies here
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        
        // Register the Dingo JWT Auth Provider
        app('Dingo\Api\Auth\Auth')->extend('jwt', function ($app) {
            return new JWT($app['PHPOpenSourceSaver\JWTAuth\JWTAuth']);
        });
    }
} 