<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DingoDisablerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // You can add services to override or disable Dingo API components here
        // Currently, we're not disabling anything as we want to use Dingo API
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // We can set up any additional Dingo API configurations here
    }
}
