<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\ProviderInterface::class, \App\Services\RapidApiService::class);

        // Self-healing database connection config for Supabase prepared statements.
        // Force session-mode port 5432 instead of transaction-mode port 6543 to avoid Prepared Statement errors.
        $dbUrl = config('database.connections.pgsql.url');
        if ($dbUrl && str_contains($dbUrl, 'pooler.supabase.com')) {
            $dbUrl = str_replace(':6543', ':5432', $dbUrl);
            config(['database.connections.pgsql.url' => $dbUrl]);
        }
        if (config('database.connections.pgsql.port') == '6543') {
            config(['database.connections.pgsql.port' => '5432']);
        }
        config(['database.connections.pgsql.options.' . \PDO::ATTR_EMULATE_PREPARES => false]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (isset($_ENV['VERCEL_URL']) || isset($_SERVER['VERCEL_URL']) || env('APP_ENV') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
