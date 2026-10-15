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
        // The app talks German, and so do its mails: the framework would
        // otherwise send its English default text. Only the footer line of the
        // mail template ("All rights reserved.") comes from lang/de.json.
        VerifyEmail::toMailUsing(fn (User $notifiable, string $url) => (new MailMessage)
            ->subject('Bitte bestätige deine E-Mail-Adresse')
            ->greeting('Hallo '.$notifiable->displayName().'!')
            ->line('bitte bestätige deine E-Mail-Adresse, damit du '.config('app.name').' nutzen kannst.')
            ->action('E-Mail-Adresse bestätigen', $url)
            ->line('Der Link ist eine Stunde lang gültig.')
            ->line('Wenn du kein Konto angelegt hast, kannst du diese E-Mail einfach ignorieren.')
            ->salutation('Viele Grüße von '.config('app.name')));

        // "Passwort vergessen?" - the link carries the one-time token and the
        // address of the account, so the form is already filled in.
        ResetPassword::toMailUsing(function (User $notifiable, string $token): MailMessage {
            $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Passwort zurücksetzen')
                ->greeting('Hallo '.$notifiable->displayName().'!')
                ->line('du hast ein neues Passwort für '.config('app.name').' angefordert.')
                ->action('Neues Passwort wählen', url(route('password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false)))
                ->line('Der Link ist '.$expire.' Minuten lang gültig.')
                ->line('Wenn du das nicht angefordert hast, kannst du diese E-Mail einfach ignorieren.')
                ->salutation('Viele Grüße von '.config('app.name'));
        });
    }
}
