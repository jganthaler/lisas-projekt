<?php

namespace Tests\Feature;

use App\Livewire\Profile\ProfilePage;
use App\Models\InventoryItem;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * The language of the app: German, Italian or English, picked in the profile.
 *
 * The choice lives on the account, so every page - and every mail - follows it.
 * Without a choice the app keeps German, which is what guests and brand new
 * accounts get.
 */
class LanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_app_starts_in_german(): void
    {
        $user = User::factory()->create();

        $this->assertSame('de', $user->localeOrDefault());

        $this->actingAs($user)
            ->get(route('freezer.closed'))
            ->assertOk()
            ->assertSee('Meine Gefrierschränke');
    }

    public function test_guests_keep_the_language_of_the_app(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Willkommen zurück');
    }

    /**
     * The three languages are offered by name and in their own language, so
     * "Deutsch" stays recognisable no matter what the app is showing.
     */
    public function test_the_profile_offers_the_three_languages(): void
    {
        $user = User::factory()->create();

        $page = Livewire::actingAs($user)->test(ProfilePage::class);

        $page->assertSet('locale', 'de')
            ->assertSee('Sprache')
            ->assertSee('Deutsch')
            ->assertSee('Italiano')
            ->assertSee('English');
    }

    /**
     * Die Sprache steht direkt unter dem Profilbild: sie gehört zu den Angaben,
     * die das Konto selbst betreffen, und wird beim Einrichten zuerst gebraucht.
     */
    public function test_the_language_is_chosen_right_below_the_profile_picture(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProfilePage::class)
            ->assertSeeInOrder(['Bild auswählen', 'Sprache', 'Angaben', 'Passwort ändern']);
    }

    public function test_the_language_can_be_changed(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProfilePage::class)
            ->call('setLocale', 'it')
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('it', $user->refresh()->locale);

        // The flash of the switch is already written in the new language.
        $this->assertSame('Lingua modificata.', session('status'));
    }

    /**
     * Kopfzeile und untere Leiste liegen im Layout außerhalb der Komponente -
     * ein Livewire-Update würde "Gefrierschrank", "Inventar" und "MHD" in der
     * alten Sprache stehen lassen. Darum beantwortet die Wahl den Klick mit
     * einer geladenen Seite, und die kommt ganz in der neuen Sprache zurück.
     */
    public function test_switching_the_language_reloads_the_app_frame(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProfilePage::class)
            ->call('setLocale', 'it')
            ->assertRedirect(route('profile.edit'));

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Lingua')
            ->assertSee('Lingua modificata.')
            ->assertSee('Congelatore')
            ->assertSee('Inventario')
            ->assertSee('Scadenza')
            ->assertDontSee('Gefrierschrank')
            ->assertDontSee('Deine Angaben und dein Profilbild');
    }

    public function test_every_page_follows_the_chosen_language(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->get(route('freezer.closed'))
            ->assertOk()
            ->assertSee('My freezers')
            ->assertSee('Edit drawers')
            ->assertDontSee('Meine Gefrierschränke');
    }

    public function test_an_unknown_language_is_ignored(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProfilePage::class)
            ->call('setLocale', 'kl')
            ->assertNoRedirect()
            ->assertSet('locale', 'de')
            ->assertSet('feedback', null);

        $this->assertSame('de', $user->refresh()->locale);
    }

    /**
     * A freezer that is provisioned while the app speaks Italian is named in
     * Italian - the name is stored, so it keeps the language of that moment.
     */
    public function test_a_new_freezer_carries_the_chosen_language(): void
    {
        $user = User::factory()->create(['locale' => 'it']);

        $this->actingAs($user)->get(route('freezer.closed'))->assertOk();

        $freezer = $user->ownedFreezers()->firstOrFail();

        $this->assertSame('Il mio congelatore', $freezer->name);
        $this->assertSame(
            ['Cassetto 1', 'Cassetto 2', 'Cassetto 3'],
            $freezer->drawers()->pluck('name')->all()
        );
    }

    /**
     * Jede Kategorie wird in jeder Sprache benannt: gespeichert wird der
     * Schlüssel (siehe InventoryItem::CATEGORY_*), angezeigt das Etikett aus
     * lang/<Sprache>/items.php. Fehlt eine Übersetzung, stünde im Filter und in
     * der Artikel-Auswahl "items.categories.…" statt eines Wortes.
     */
    public function test_every_category_is_named_in_every_language(): void
    {
        foreach (Locales::codes() as $locale) {
            app()->setLocale($locale);

            $labels = InventoryItem::categoryLabels();

            foreach ($labels as $key => $label) {
                $this->assertNotSame(
                    'items.categories.'.$key,
                    $label,
                    'Die Kategorie "'.$key.'" fehlt in der Sprache "'.$locale.'".'
                );
                $this->assertNotSame('', trim($label));
            }

            // Zwei Kategorien mit demselben Wort ließen sich im Filter nicht
            // auseinanderhalten.
            $this->assertSame(count($labels), count(array_unique($labels)));
        }
    }

    /**
     * Jede Schnellauswahl des MHD ist in jeder Sprache benannt: angezeigt wird
     * das Etikett aus lang/<Sprache>/drawers.php, gerechnet wird mit der
     * Zeitspanne aus InventoryItem::BEST_BEFORE_PRESETS. Fehlt eine
     * Übersetzung, stünde im Formular "drawers.quick_best_before.3_months"
     * statt "3 Monate".
     */
    public function test_every_quick_choice_of_the_best_before_date_is_named_in_every_language(): void
    {
        foreach (Locales::codes() as $locale) {
            app()->setLocale($locale);

            $labels = InventoryItem::bestBeforePresetLabels();

            $this->assertCount(count(InventoryItem::BEST_BEFORE_PRESETS), $labels);

            foreach ($labels as $key => $label) {
                $this->assertNotSame(
                    'drawers.quick_best_before.'.$key,
                    $label,
                    'Die Schnellauswahl "'.$key.'" fehlt in der Sprache "'.$locale.'".'
                );
                $this->assertNotSame('', trim($label));
            }

            // Zwei Auswahlen mit demselben Wort ließen sich nicht unterscheiden.
            $this->assertSame(count($labels), count(array_unique($labels)));
        }
    }

    /**
     * Laravel renders a notification inside the locale of its recipient, which
     * the app answers with User::preferredLocale() - so the mail is written in
     * the language of the account.
     */
    public function test_the_verification_mail_speaks_the_language_of_the_account(): void
    {
        $user = User::factory()->unverified()->create(['locale' => 'it']);

        $user->sendEmailVerificationNotification();

        $transport = Mail::mailer()->getSymfonyTransport();

        $this->assertInstanceOf(ArrayTransport::class, $transport);

        $message = $transport->messages()->first()?->getOriginalMessage();

        $this->assertInstanceOf(Email::class, $message);
        $this->assertSame('Conferma il tuo indirizzo e-mail', $message->getSubject());
    }

    /**
     * The name of the browser tab is translated as well: the components hand the
     * key of their page to the layout (see the #[Title] attributes).
     */
    public function test_the_browser_title_follows_the_language(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->get(route('freezer.closed'))
            ->assertOk()
            ->assertSee('<title>Freezer · Coolventory</title>', false);

        // A guest has no language yet, so the default of the app is used - even
        // in a process that just answered in English (App::setLocale() writes
        // into config('app.locale'), which must not decide the fallback).
        auth()->logout();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<title>Anmelden · Coolventory</title>', false);
    }

    /**
     * The codes of the offered languages are the ones the profile stores.
     */
    public function test_only_the_offered_languages_are_supported(): void
    {
        $this->assertSame(['de', 'it', 'en'], Locales::codes());
        $this->assertSame('de', Locales::default());
        $this->assertTrue(Locales::supports('it'));
        $this->assertFalse(Locales::supports('fr'));
        $this->assertFalse(Locales::supports(null));
        $this->assertSame('Italiano', Locales::label('it'));
    }
}
