<?php

namespace App\Providers;

use App\Models\User;
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
        // The app talks German, and so does the verification mail: without a
        // "lang" folder the framework would send its English default text.
        VerifyEmail::toMailUsing(fn (User $notifiable, string $url) => (new MailMessage)
            ->subject('Bitte bestätige deine E-Mail-Adresse')
            ->greeting('Hallo '.$notifiable->displayName().'!')
            ->line('bitte bestätige deine E-Mail-Adresse, damit du '.config('app.name').' nutzen kannst.')
            ->action('E-Mail-Adresse bestätigen', $url)
            ->line('Der Link ist eine Stunde lang gültig.')
            ->line('Wenn du kein Konto angelegt hast, kannst du diese E-Mail einfach ignorieren.')
            ->salutation('Viele Grüße von '.config('app.name')));
    }
}
