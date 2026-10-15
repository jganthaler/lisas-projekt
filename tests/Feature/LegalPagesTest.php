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
 * nach dem Löschen eines Kontos), sie nennen den Anbieter aus der Konfiguration
 * (config/legal.php, LEGAL_*) und sie dürfen keinen ungefüllten Platzhalter
 * ausliefern.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private const PAGES = ['legal.imprint', 'legal.privacy', 'legal.terms'];

    protected function setUp(): void
    {
        parent::setUp();

        // Im Test stehen die Angaben des Anbieters in der Konfiguration, weil
        // die Umgebung der Anwendung (noch) keine hat.
        config([
            'legal.operator.name' => 'Lisa Muster',
            'legal.operator.street' => 'Musterweg 1',
            'legal.operator.city' => '12345 Musterstadt',
            'legal.operator.email' => 'lisa@example.com',
            'legal.operator.phone' => '+49 123 456789',
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

    public function test_the_imprint_names_the_operator(): void
    {
        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Lisa Muster')
            ->assertSee('Musterweg 1')
            ->assertSee('12345 Musterstadt')
            ->assertSee('lisa@example.com')
            ->assertSee('+49 123 456789')
            ->assertSee('§ 5 DDG');
    }

    /**
     * Die Datenschutzerklärung nennt den Verantwortlichen, die Empfänger und das
     * eine Cookie, das die Anwendung setzt.
     */
    public function test_the_privacy_policy_names_the_controller_and_the_cookie(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Lisa Muster')
            ->assertSee('Musterhost GmbH')
            ->assertSee('Mustermail GmbH')
            ->assertSee('laravel_session')
            ->assertSee('Art. 15 DSGVO');
    }

    public function test_the_terms_of_use_describe_the_service(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Nutzungsbedingungen')
            ->assertSee('Konto löschen')
            ->assertSee('16 Jahre');
    }

    /**
     * Ohne Anbieterangaben fehlt der Anwendung das Impressum: Dann zeigt die
     * Seite einen Hinweis statt einer leeren Anschrift.
     */
    public function test_a_missing_operator_is_announced(): void
    {
        config([
            'legal.operator.name' => null,
            'legal.operator.street' => null,
            'legal.operator.city' => null,
            'legal.operator.email' => null,
        ]);

        $this->assertFalse(Legal::isComplete());

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Anbieterangaben fehlen')
            ->assertSee('Name des Anbieters')
            ->assertSee('E-Mail-Adresse für Kontakt')
            ->assertSee('LEGAL_');
    }

    public function test_the_operator_details_survive_without_a_phone_number(): void
    {
        config(['legal.operator.phone' => null]);

        $this->assertTrue(Legal::isComplete());

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Lisa Muster')
            ->assertDontSee('Telefon');
    }

    /**
     * Ein stehen gebliebenes ":operator" wäre ein Fehler: Die Texte werden vor
     * dem Rendern gefüllt (App\Support\Legal).
     */
    public function test_the_pages_never_ship_with_an_unfilled_placeholder(): void
    {
        foreach (self::PAGES as $name) {
            $html = $this->get(route($name))->assertOk()->getContent();

            foreach ([':app', ':url', ':operator', ':hosting', ':mail_provider', ':date', ':location'] as $placeholder) {
                $this->assertStringNotContainsString(
                    $placeholder,
                    $html,
                    "Der Platzhalter {$placeholder} steht noch auf der Seite {$name}.",
                );
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
            'legal.operator.name' => null,
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
     * sichtbar sein: Die Bedingungen werden so Vertragsbestandteil (§ 305 Abs. 2
     * BGB), und die Information nach Art. 13 DSGVO steht vor der Erhebung.
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
