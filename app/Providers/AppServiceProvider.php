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
         $this->app->singleton(WatermarkService::class, function () {
            return new WatermarkService();
        });
    }

    /**
     * Bootstrap any application services.
     */
   public function boot(): void
{
    \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function ($user, string $token) {
        return 'https://sbraisolutions.com/reset-password?token=' . $token
             . '&email=' . urlencode($user->email);
    });
}
}
