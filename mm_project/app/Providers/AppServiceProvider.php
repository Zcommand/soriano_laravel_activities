<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        Gate::define('manage-members', fn (User $user): bool => $user->is_admin === true);

        RateLimiter::for('staff-login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower(is_string($request->input('email')) ? $request->input('email') : '').'|'.$request->ip()),
            Limit::perMinute(30)->by($request->ip()),
        ]);

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
