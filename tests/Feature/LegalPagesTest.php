<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Legal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Impressum, Datenschutzerklärung und Nutzungsbedingungen.
 *
 * Die drei Seiten müssen ohne Anmeldung lesbar sein (vor der Registrierung und
 * nach dem Löschen eines Kontos), sie nennen die Kontaktangabe aus der
 * Konfiguration (config/legal.php, LEGAL_OPERATOR_EMAIL) und die öffentliche
 * Adresse des Angebots (LEGAL_SITE_URL, im Geltungsbereich des Impressums); sie
 * dürfen keinen ungefüllten Platzhalter ausliefern. Der Anbieter sitzt in
 * Italien, die Texte nennen deshalb die italienischen Vorschriften (D.Lgs.
 * 70/2003, D.Lgs. 196/2003, Codice del Consumo) neben der DSGVO. Name und
 * Anschrift stehen bewusst nicht im Impressum: Für ein privates, nicht
 * kommerzielles Angebot verlangt italienisches Recht keine Anbieterangaben.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private const PAGES = ['legal.imprint', 'legal.privacy', 'legal.terms'];

    protected function setUp(): void
    {
        parent::setUp();

        // Im Test steht die Kontaktangabe in der Konfiguration, weil die
        // Umgebung der Anwendung (noch) keine hat.
        config([
            'legal.operator.email' => 'lisa@example.com',
            'legal.hosting.provider' => 'Musterhost GmbH',
            'legal.hosting.location' => 'Frankfurt, Deutschland',
            'legal.mail.provider' => 'Mustermail GmbH',
            'legal.mail.location' => 'Berlin, Deutschland',
            'legal.updated_at' => '2026-10-15',
        ]);
    }

    public function test_the_legal_pages_are_readable_without_a_session(): void
    {
        foreach (self::PAGES as $name) {
            $this->get(route($name))->assertOk();
        }

        // Der Name der Seite steht im Browser-Tab: Die Views geben den Schlüssel
        // an das Layout weiter (wie die Livewire-Seiten über #[Title]).
        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('<title>Impressum · Coolventory</title>', false);
    }

    /**
     * Das Impressum nennt als einzige Angabe die E-Mail-Adresse und die
     * italienischen Vorschriften, nach denen sich die Seite richtet.
     *
     * Name, Straße und Ort gibt es dort bewusst nicht: Für ein privates, nicht
     * kommerzielles Angebot verlangt italienisches Recht keine Anbieterangaben
     * (Art. 7 D.Lgs. 70/2003 und Art. 49 Codice del Consumo treffen gewerbliche
     * Dienste). Selbst ein Eintrag in der Umgebung erscheint nicht auf der
     * Seite - so bleibt die Angabe privat, auch wenn jemand sie später wieder
     * in die .env schreibt.
     */
    public function test_the_imprint_shows_the_email_address_and_nothing_else(): void
    {
        $this->assertNull(config('legal.operator.name'));
        $this->assertNull(config('legal.operator.street'));
        $this->assertNull(config('legal.operator.city'));

        config([
            'legal.operator.name' => 'Lisa Muster',
            'legal.operator.street' => 'Musterweg 1',
            'legal.operator.city' => '12345 Musterstadt',
        ]);

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('lisa@example.com')
            ->assertSee('mailto:lisa@example.com', false)
            ->assertDontSee('Lisa Muster')
            ->assertDontSee('Musterweg 1')
            ->assertDontSee('12345 Musterstadt')
            ->assertDontSee('Telefon')
            // Kontakt, Haftung und Streitbeilegung: italienisches Recht statt
            // § 5 DDG.
            ->assertSee('Art. 7 D.Lgs. 70/2003')
            ->assertSee('Art. 16 D.Lgs. 70/2003')
            ->assertSee('Art. 17 D.Lgs. 70/2003')
            ->assertSee('D.Lgs. 130/2015')
            ->assertDontSee('§ 5 DDG');
    }

    /**
     * Der Geltungsbereich des Impressums nennt die öffentliche Adresse des
     * Angebots (LEGAL_SITE_URL, config/legal.php) - nicht die Adresse der
     * laufenden Installation: Lokal wäre das "http://localhost:8000", und das
     * gehört nicht in ein Impressum. (Bilder, Skripte und Links der Seite kommen
     * weiterhin von APP_URL; nur der Satz im Impressum nennt die öffentliche
     * Adresse.) Ohne Angabe steht dort ein neutraler Satz statt einer Lücke.
     */
    public function test_the_imprint_names_the_public_address(): void
    {
        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Dieses Impressum gilt für Coolventory unter www.coolventory.com.')
            ->assertDontSee('unter '.config('app.url'));

        config(['legal.site' => null]);

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Dieses Impressum gilt für Coolventory unter der veröffentlichten Adresse.')
            ->assertDontSee(':site');
    }

    /**
     * Die Datenschutzerklärung nennt den Verantwortlichen samt Kontaktadresse,
     * die Empfänger, das eine Cookie, das die Anwendung setzt, und in Italien
     * die Aufsichtsbehörde.
     */
    public function test_the_privacy_policy_names_the_controller_and_the_cookie(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('lisa@example.com')
            ->assertSee('Musterhost GmbH')
            ->assertSee('Mustermail GmbH')
            ->assertSee('laravel_session')
            ->assertSee('Art. 15 DSGVO')
            // Italienisches Recht: Codice Privacy und der Garante als Behörde.
            ->assertSee('D.Lgs. 196/2003')
            ->assertSee('Garante per la protezione dei dati personali')
            ->assertDontSee('TDDDG');
    }

    public function test_the_terms_of_use_describe_the_service(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Nutzungsbedingungen')
            ->assertSee('Konto löschen')
            ->assertSee('16 Jahre')
            // Der Anbieter sitzt in Italien: italienisches Recht, Codice del
            // Consumo, Gerichtsstand des Verbrauchers.
            ->assertSee('italienisches Recht')
            ->assertSee('Codice del Consumo')
            ->assertSee('Art. 66-bis')
            // Eine Haftungsklausel steht bewusst nicht in den Bedingungen.
            ->assertDontSee('Haftung');
    }

    /**
     * Ohne Kontaktangabe fehlt dem Impressum die einzige Angabe, die es nennt:
     * Dann zeigt die Seite einen Hinweis statt eines leeren Kontakts.
     */
    public function test_a_missing_contact_address_is_announced(): void
    {
        config(['legal.operator.email' => null]);

        $this->assertFalse(Legal::isComplete());

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Kontaktangabe fehlt')
            ->assertSee('E-Mail-Adresse für Kontakt')
            ->assertSee('LEGAL_OPERATOR_EMAIL');
    }

    /**
     * Ein stehen gebliebenes ":operator_email" wäre ein Fehler: Die Texte
     * werden vor dem Rendern gefüllt (App\Support\Legal). Geprüft wird in allen
     * drei Sprachen, weil jede Sprache ihre eigenen Texte mitbringt.
     */
    public function test_the_pages_never_ship_with_an_unfilled_placeholder(): void
    {
        $placeholders = [':app', ':site', ':operator_email', ':hosting', ':mail_provider', ':date', ':location'];

        foreach (['de', 'en', 'it'] as $locale) {
            $user = User::factory()->create(['locale' => $locale]);

            foreach (self::PAGES as $name) {
                $html = $this->actingAs($user)->get(route($name))->assertOk()->getContent();

                foreach ($placeholders as $placeholder) {
                    $this->assertStringNotContainsString(
                        $placeholder,
                        $html,
                        "Der Platzhalter {$placeholder} steht noch auf der Seite {$name} ({$locale}).",
                    );
                }
            }
        }
    }

    /**
     * Auch ohne hinterlegte Anbieter liest sich die Erklärung vollständig: Statt
     * einer Lücke steht dort ein neutraler Text.
     */
    public function test_the_texts_carry_the_page_while_the_providers_are_missing(): void
    {
        config([
            'legal.operator.email' => null,
            'legal.hosting.provider' => null,
            'legal.hosting.location' => null,
            'legal.mail.provider' => null,
            'legal.mail.location' => null,
            'legal.updated_at' => null,
        ]);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('der Hosting-Anbieter dieser Anwendung')
            ->assertSee('beim Anbieter zu erfragen')
            ->assertSee('die im Impressum genannte Adresse');
    }

    /**
     * Vor der Registrierung und in der App muss der Weg zu den Rechtstexten
     * sichtbar sein: Die Bedingungen werden so Vertragsbestandteil (Art. 1341
     * Codice Civile; für Verbraucher Art. 33 Codice del Consumo), und die
     * Information nach Art. 13 DSGVO steht vor der Erhebung.
     */
    public function test_the_pages_are_linked_where_they_are_needed(): void
    {
        foreach ([route('login'), route('register')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            foreach (self::PAGES as $name) {
                $this->assertStringContainsString(route($name), $html, "Der Link {$name} fehlt auf {$url}.");
            }
        }

        $user = User::factory()->create();

        $profile = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();

        foreach (self::PAGES as $name) {
            $this->assertStringContainsString(route($name), $profile);
        }
    }

    public function test_the_registration_refers_to_the_terms_and_the_privacy_policy(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Mit der Registrierung stimmst du den')
            ->assertSee('Nutzungsbedingungen')
            ->assertSee('Datenschutzerklärung');
    }

    public function test_the_legal_pages_follow_the_language_of_the_account(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy policy')
            ->assertSee('Art. 15 GDPR')
            ->assertDontSee('Datenschutzerklärung');
    }

    public function test_the_date_of_the_last_change_is_shown(): void
    {
        $this->get(route('legal.terms'))->assertOk()->assertSee('Stand: 15.10.2026');

        // Ohne Datum in der Konfiguration entfällt die Zeile.
        config(['legal.updated_at' => null]);

        $this->get(route('legal.terms'))->assertOk()->assertDontSee('Stand:');
    }
}
