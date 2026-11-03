<?php

namespace App\Providers;

use App\Http\Controllers\LivewireUpdateController;
use App\Models\Menu;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ContentSanitizer;
use App\Services\LivewireOutputEncoding;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ContentSanitizer::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureLivewireOutput();
        $this->configureAuthorization();
        $this->configureAuthenticationEvents();
        $this->configurePublicViewData();
    }

    protected function configureLivewireOutput(): void
    {
        Livewire::setUpdateRoute(static fn ($handle, $path) => Route::post($path, LivewireUpdateController::class));
        Livewire::listen('response', static fn () => static fn (array $payload): array => app(LivewireOutputEncoding::class)->repair($payload));
    }

    /**
     * Configure application-wide defaults.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }

    /**
     * Configure application authorization rules.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(
            fn (User $user, string $ability): ?bool => $user->hasRole('Super Administrator') ? true : null,
        );
    }

    /**
     * Register authentication audit event listeners.
     */
    protected function configureAuthenticationEvents(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Successful Login
        |--------------------------------------------------------------------------
        */
        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            $event->user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $this->currentRequestIp(),
            ])->saveQuietly();

            app(AuditLogger::class)->log(
                event: 'auth.login',
                description: 'User successfully signed in.',
                actor: $event->user,
                subject: $event->user,
                newValues: [
                    'guard' => $event->guard,
                    'remember' => $event->remember,
                ],
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Logout
        |--------------------------------------------------------------------------
        */
        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            app(AuditLogger::class)->log(
                event: 'auth.logout',
                description: 'User signed out.',
                actor: $event->user,
                subject: $event->user,
                newValues: [
                    'guard' => $event->guard,
                ],
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Failed Login
        |--------------------------------------------------------------------------
        */
        Event::listen(Failed::class, function (Failed $event): void {
            $subject = $event->user instanceof User
                ? $event->user
                : null;

            $email = $event->credentials['email'] ?? null;

            app(AuditLogger::class)->log(
                event: 'auth.login-failed',
                description: 'Failed login attempt.',
                actor: null,
                subject: $subject,
                newValues: [
                    'email' => is_string($email)
                        ? mb_strtolower(trim($email))
                        : null,

                    'guard' => $event->guard,
                ],
            );
        });
    }

    /**
     * Share site identity and public navigation with the public layout.
     */
    protected function configurePublicViewData(): void
    {
        View::composer('public.maintenance', static function (ViewInstance $view): void {
            $settings = $view->getData()['settings'] ?? null;
            if (! $settings instanceof SiteSetting || app()->getLocale() !== 'si') {
                return;
            }

            $localized = clone $settings;
            $localized->setAttribute('maintenance_message', $settings->localized('maintenance_message', 'si'));
            $view->with('settings', $localized);
        });

        View::composer(
            [
                'layouts.public',
                'theme-*::layout',
            ],
            function (ViewInstance $view): void {
                $settings = Schema::hasTable('site_settings')
                    ? SiteSetting::query()->first()
                    : null;

                $primaryMenu = Schema::hasTable('menus') && Schema::hasTable('menu_items')
                    ? Menu::query()
                        ->active()
                        ->where('location', 'primary')
                        ->with(['rootItems.translations', 'rootItems.children.translations'])
                        ->first()
                    : null;

                $socialLinks = Schema::hasTable('social_links')
                    ? SocialLink::query()->active()->get()
                    : collect();

                $view->with([
                    'siteSettings' => $settings,
                    'primaryMenu' => $primaryMenu,
                    'publicSocialLinks' => $socialLinks,
                ]);
            });
    }

    /**
     * Get the current request IP address safely.
     */
    protected function currentRequestIp(): ?string
    {
        if (! $this->app->bound('request')) {
            return null;
        }

        return request()->ip();
    }
}
