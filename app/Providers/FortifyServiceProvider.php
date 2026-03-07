<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->instance(\Laravel\Fortify\Contracts\LoginResponse::class, new class implements \Laravel\Fortify\Contracts\LoginResponse
        {
            public function toResponse($request): \Symfony\Component\HttpFoundation\Response
            {
                /** @var \App\Models\User|null $user */
                $user = auth()->user();

                if ($user?->isFrontDesk()) {
                    return redirect()->route('front-desk.index');
                }

                return redirect()->intended(route('dashboard'));
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::twoFactorChallengeView(fn () => view('livewire.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
