<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Wallet;
use App\Observers\WalletObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Wallet::observe(WalletObserver::class);

        Number::useCurrency('EUR');

        Number::macro('currencyCents', function (int|float $number, string $in = '', ?string $locale = null) {
            return Number::currency((int) $number / 100, $in, $locale);
        });

        Password::defaults(function () {
            return app()->isProduction() ? Password::min(8)
                ->numbers()
                ->mixedCase()
                ->uncompromised() : Password::min(6);
        });

        RateLimiter::for('api.login', function (Request $request) {
            return Limit::perMinutes(5, 5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
