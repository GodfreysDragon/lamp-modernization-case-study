<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // In development and tests, an accidental lazy load (an N+1 query) throws instead of failing silently.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Inertia props are already namespaced, so skip the {"data": ...} wrapper.
        // Paginated collections still come through as {data, links, meta}.
        JsonResource::withoutWrapping();

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('username')).'|'.$request->ip()));
    }
}
