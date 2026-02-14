<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;


class FortifyServiceProvider extends ServiceProvider
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
        // Allow login with either mobile number or email + password (bcrypt)
        Fortify::authenticateUsing(function ($request) {
            $login = trim((string) $request->input('email'));
            $mobile = preg_replace('/\s+/', '', $login);

            $user = User::query()
                ->where('email', $login)
                ->orWhere('contact_number', $login)
                ->orWhere('contact_number', $mobile)
                ->first();

            if (! $user) {
                return null;
            }

            // Block inactive accounts with a clear message
            if (! $user->is_active) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    Fortify::username() => __('Your account is inactive. Please contact admin.'),
                ]);
            }

            return Hash::check($request->password, $user->password) ? $user : null;
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Fortify::authenticateUsing(function (LoginRequest $request) {
        //     $user = User::where('email', $request->email)->first();

        //     if ($user && ! $user->is_active) {
        //         throw ValidationException::withMessages([
        //             Fortify::username() => __('Your account is inactive. Please contact admin.'),
        //         ]);
        //     }

        //     return $user;
        // });
    }
}
