<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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
        // The mails speak the language of the account: Laravel renders a
        // notification inside the locale of its recipient (User::preferredLocale()),
        // so every text here is translated in that language. Only the footer
        // line of the mail template ("All rights reserved.") comes from
        // lang/<language>.json.
        VerifyEmail::toMailUsing(fn (User $notifiable, string $url) => (new MailMessage)
            ->subject(__('mail.verification.subject'))
            ->greeting(__('mail.verification.greeting', ['name' => $notifiable->displayName()]))
            ->line(__('mail.verification.line', ['app' => config('app.name')]))
            ->action(__('mail.verification.action'), $url)
            ->line(__('mail.verification.expiry'))
            ->line(__('mail.verification.ignore'))
            ->salutation(__('mail.verification.salutation', ['app' => config('app.name')])));

        // "Passwort vergessen?" - the link carries the one-time token and the
        // address of the account, so the form is already filled in.
        ResetPassword::toMailUsing(function (User $notifiable, string $token): MailMessage {
            $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject(__('mail.reset.subject'))
                ->greeting(__('mail.reset.greeting', ['name' => $notifiable->displayName()]))
                ->line(__('mail.reset.line', ['app' => config('app.name')]))
                ->action(__('mail.reset.action'), url(route('password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false)))
                ->line(__('mail.reset.expiry', ['minutes' => $expire]))
                ->line(__('mail.reset.ignore'))
                ->salutation(__('mail.reset.salutation', ['app' => config('app.name')]));
        });
    }
}
