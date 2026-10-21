<?php

namespace Tests\Feature;

use App\Livewire\Freezer\ClosedFreezer;
use App\Models\Freezer;
use App\Models\FreezerMembership;
use App\Models\InventoryItem;
use App\Models\User;
use App\Support\FreezerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Managing the freezers themselves on the "Gefrierschrank" page: creating
 * another freezer, switching between them and renaming the owned ones.
 */
class ClosedFreezerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Freezer $freezer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->freezer = Freezer::factory()->withDrawers()->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Gefrierschrank Küche',
        ]);
    }

    /**
     * @return Testable<ClosedFreezer>
     */
    private function page(User $user)
    {
        return Livewire::actingAs($user)->test(ClosedFreezer::class);
    }

    /**
     * Somebody who may work in the freezer without owning it.
     */
    private function member(): User
    {
        $member = User::factory()->create();

        $this->freezer->memberships()->create([
            'user_id' => $member->getKey(),
            'role' => FreezerMembership::ROLE_MEMBER,
        ]);

        return $member;
    }

    /**
     * Die Namen, die unter dem Kopf einer Schublade stehen - in der Reihenfolge,
     * in der die Startseite sie zeigt. Gesucht wird nur in der Karte dieser
     * Schublade: sie reicht von ihrem Namen bis zum Anfang der nächsten. Eine
     * leere Schublade nennt keinen, sie trägt nur den Hinweis, dass sie leer ist.
     *
     * @return list<string>
     */
    private function namesIn(string $html, string $drawerName): array
    {
        $start = strpos($html, 'font-bold text-frost-900">'.$drawerName.'</span>');

        if ($start === false) {
            return [];
        }

        $card = substr($html, $start);
        $next = strpos($card, 'drawer-row');

        $card = $next === false ? $card : substr($card, 0, $next);

        preg_match('/<ul[^>]*>(.*?)<\/ul>/s', $card, $list);
        preg_match_all('/<li[^>]*>(.*?)<\/li>/s', $list[1] ?? '', $matches);

        return array_map('trim', $matches[1]);
    }

    public function test_the_page_lists_every_freezer_of_the_user(): void
    {
        Freezer::factory()->sharedWith($this->owner)->create(['name' => 'Truhe im Keller']);

        $page = $this->page($this->owner);

        $this->assertSame(
            ['Gefrierschrank Küche', 'Truhe im Keller'],
            $page->viewData('freezers')->pluck('name')->all()
        );

        // The owned freezer is the active one, as long as nothing was chosen.
        $this->assertTrue($page->viewData('freezer')->is($this->freezer));

        $page->assertSee('Meine Gefrierschränke')
            ->assertSee('Gefrierschrank Küche')
            ->assertSee('Truhe im Keller')
            // The form for another freezer only appears on request.
            ->assertDontSee('Neuer Gefrierschrank');
    }

    /**
     * Gezählt wird auf der Startseite nirgends: das Feld „Meine Gefrierschränke“
     * nennt den aktiven Gefrierschrank, aber nicht die Zahl seiner Artikel - die
     * steht in den Schubladen selbst. Eine zweite Karte mit Icon, Namen und
     * Zähler über der Liste gibt es nicht, und der aktive Gefrierschrank ist in
     * der Liste des Feldes mit „aktiv“ gekennzeichnet.
     */
    public function test_the_start_page_never_counts_the_items(): void
    {
        $drawer = $this->freezer->drawers()->firstOrFail();

        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Zucchini']);
        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Äpfel']);
        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Erbsen']);

        $this->page($this->owner)
            // Kein Zähler („3 Artikel in 3 Schubladen“) und keine zweite Karte des
            // aktiven Gefrierschranks - die trug als einzige diesen Schatten.
            ->assertDontSee('3 Artikel')
            ->assertDontSeeHtml('shadow-brand-600/25')
            // Sein Name steht im Feld, mit der Markierung „aktiv“.
            ->assertSee('Meine Gefrierschränke')
            ->assertSee('Gefrierschrank Küche')
            ->assertSee('aktiv');
    }

    /**
     * Das Feld zeigt den aktiven Gefrierschrank als eigenen Block: die Kachel mit
     * dem Icon des Gefrierschranks, daneben sein Name in der größten Schrift der
     * Seite und darunter, wem er gehört. Die Fläche des Blocks trägt den Akzent
     * der App und hebt ihn damit über die Karte.
     */
    public function test_the_field_shows_the_active_freezer_in_its_own_block(): void
    {
        $html = $this->page($this->owner)->html();

        $this->assertMatchesRegularExpression('/class="grid h-11 w-11[^"]*bg-brand-600 text-white"/', $html);
        $this->assertStringContainsString('text-base font-bold text-frost-900">Gefrierschrank Küche</span>', $html);
        $this->assertStringContainsString('rounded-2xl bg-brand-50', $html);
        $this->assertStringContainsString('Dein Gefrierschrank', $html);
    }

    /**
     * Der Name des aktiven Gefrierschranks ist der Knopf, der die Liste aller
     * Gefrierschränke aufzieht: jeder steht darin mit seiner Rolle, der aktive
     * zusätzlich mit dem Häkchen und dem Wort „aktiv“. Die Liste steht im Fluss
     * der Seite (nicht über den Schubladen), damit nichts verdeckt wird.
     */
    public function test_the_name_opens_the_list_of_all_freezers(): void
    {
        $shared = Freezer::factory()->sharedWith($this->owner)->create(['name' => 'Truhe im Keller']);

        $this->page($this->owner)
            ->assertSeeHtml('x-on:click="open = ! open"')
            ->assertSeeHtml('x-show="open"')
            ->assertSeeHtml('wire:click="selectFreezer('.$this->freezer->getKey().')"')
            ->assertSeeHtml('wire:click="selectFreezer('.$shared->getKey().')"')
            ->assertSeeHtml('aria-current="true"')
            ->assertSee('Mit dir geteilt')
            ->assertSee('aktiv');
    }

    /**
     * Die Pfeile links und rechts neben dem Namen gehen die Gefrierschränke
     * Schritt für Schritt durch - in der Reihenfolge der Liste, also nach dem
     * Namen - und nach dem letzten kommt wieder der erste. Jeder Gefrierschrank
     * ist damit auch ohne die Liste erreichbar, und die Wahl landet in der
     * Session (siehe FreezerContext).
     */
    public function test_the_arrows_walk_the_freezers_and_wrap_around(): void
    {
        $second = Freezer::factory()->withDrawers()->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Truhe im Keller',
        ]);
        $third = Freezer::factory()->withDrawers()->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Vorratskammer',
        ]);

        $this->assertSame(
            ['Gefrierschrank Küche', 'Truhe im Keller', 'Vorratskammer'],
            $this->page($this->owner)->viewData('freezers')->pluck('name')->all()
        );

        $this->page($this->owner)->call('selectNextFreezer')->assertRedirect(route('freezer.closed'));
        $this->assertTrue(FreezerContext::current($this->owner)->is($second));

        $this->page($this->owner)->call('selectNextFreezer');
        $this->assertTrue(FreezerContext::current($this->owner)->is($third));

        // Nach dem letzten kommt wieder der erste ...
        $this->page($this->owner)->call('selectNextFreezer');
        $this->assertTrue(FreezerContext::current($this->owner)->is($this->freezer));

        // ... und rückwärts geht es genauso.
        $this->page($this->owner)->call('selectPreviousFreezer');
        $this->assertTrue(FreezerContext::current($this->owner)->is($third));
    }

    /**
     * Mit einem einzigen Gefrierschrank gibt es nichts zu wechseln: das Feld
     * zeigt keine Pfeile, und ein Schritt ins Leere ändert nichts.
     */
    public function test_the_field_offers_no_arrows_with_a_single_freezer(): void
    {
        $this->page($this->owner)
            ->assertDontSeeHtml('selectPreviousFreezer')
            ->assertDontSeeHtml('selectNextFreezer');

        $this->page($this->owner)->call('selectNextFreezer');

        $this->assertSame($this->freezer->getKey(), FreezerContext::id());
    }

    /**
     * Das Drei-Punkte-Menü des Feldes gehört zum aktiven Gefrierschrank: es legt
     * einen weiteren an, benennt diesen um und löscht ihn - jede Aktion mit Icon
     * und Beschriftung. Das Löschen fragt weiterhin über den Dialog der App nach
     * (siehe components/confirm-dialog.blade.php), nie über den Browser.
     */
    public function test_the_menu_holds_the_actions_of_the_active_freezer(): void
    {
        $this->page($this->owner)
            // Der Knopf des Menüs nennt den Gefrierschrank, zu dem er gehört.
            ->assertSeeHtml('aria-label="Weitere Aktionen für „Gefrierschrank Küche“"')
            ->assertSee('Gefrierschrank hinzufügen')
            ->assertSeeHtml('wire:click="startAddingFreezer"')
            ->assertSee('Gefrierschrank bearbeiten')
            ->assertSeeHtml('wire:click="startRenaming('.$this->freezer->getKey().')"')
            ->assertSee('Gefrierschrank löschen')
            ->assertSeeHtml("name: 'freezer-delete-".$this->freezer->getKey()."'");
    }

    /**
     * Einen geteilten Gefrierschrank darf nur sein Besitzer umbenennen und
     * löschen (siehe FreezerPolicy): das Menü eines Mitglieds trägt deshalb nur
     * das Anlegen eines eigenen Gefrierschranks.
     */
    public function test_a_member_sees_no_edit_or_delete_entry(): void
    {
        $member = $this->member();

        $this->page($member)
            ->assertSee('Gefrierschrank hinzufügen')
            ->assertDontSee('Gefrierschrank bearbeiten')
            ->assertDontSee('Gefrierschrank löschen')
            ->assertDontSeeHtml('startRenaming')
            ->assertDontSeeHtml('freezer-delete');
    }

    /**
     * Das Feld trägt ein Formular auf einmal: wer einen Gefrierschrank anlegt,
     * schließt das Umbenennen - und umgekehrt. Beide Formulare stehen unter dem
     * Feld des aktiven Gefrierschranks, das dabei sichtbar bleibt.
     */
    public function test_the_field_carries_one_form_at_a_time(): void
    {
        $this->page($this->owner)
            ->call('startAddingFreezer')
            ->call('startRenaming', $this->freezer->getKey())
            ->assertSet('addingFreezer', false)
            ->assertSet('editingFreezerId', $this->freezer->getKey())
            ->assertSeeHtml('wire:model="editingFreezerName"')
            ->assertSee('Name des Gefrierschranks')
            ->call('startAddingFreezer')
            ->assertSet('editingFreezerId', null)
            ->assertSet('addingFreezer', true)
            ->assertSee('Neuer Gefrierschrank');
    }

    /**
     * The start page no longer nags about items whose MHD is due: the warning pill
     * of the app frame and the MHD page carry that information.
     */
    public function test_the_start_page_does_not_warn_about_the_mhd(): void
    {
        InventoryItem::factory()->expired()->create([
            'drawer_id' => $this->freezer->drawers()->firstOrFail()->getKey(),
        ]);

        $this->page($this->owner)
            ->assertDontSee('Artikel prüfen')
            ->assertDontSee('Alles frisch')
            ->assertDontSee('abgelaufen');
    }

    /**
     * Die Schublade auf der Startseite ist ein Blick hinein: sie nennt ihren
     * Inhalt alphabetisch untereinander - die Namen, sonst nichts. Die Menge
     * eines Artikels steht in der Schublade selbst, und gezählt wird auf der
     * Startseite nirgends.
     */
    public function test_a_drawer_names_its_items_without_their_amount(): void
    {
        $drawer = $this->freezer->drawers()->firstOrFail();

        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Zucchini', 'quantity' => 2]);
        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Äpfel', 'quantity' => 3]);
        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Erbsen', 'quantity' => 5]);

        $html = $this->page($this->owner)->assertSee('Schublade 1')->html();

        // Die Startseite hat nur eine Liste: den Inhalt der Schublade. Sie nennt
        // die Namen in der Reihenfolge der Relation - „Äpfel“ vor „Erbsen“, weil
        // über den Sortierschlüssel („aepfel“) sortiert wird - und nichts
        // daneben: keine Menge, kein MHD.
        preg_match('/<ul[^>]*>(.*?)<\/ul>/s', $html, $list);

        $this->assertSame(
            'ÄpfelErbsenZucchini',
            preg_replace('/\s+/', '', strip_tags($list[1] ?? ''))
        );

        // Gezählt wird auf der Startseite nirgends: die Karte des aktiven
        // Gefrierschranks ist fort, und die Schublade nennt nur Namen.
        $this->assertSame(0, substr_count($html, '3 Artikel'));
    }

    /**
     * Der Kopf einer Schublade steht über einer Trennlinie, ihr Inhalt darunter:
     * links das Icon der Schublade, daneben ihr Name - fett und auf derselben
     * Linie -, unter dem Kopf eine Haarlinie wie in den Karten der App, und erst
     * darunter die Liste ihrer Artikel. Die Liste bleibt die alphabetische der
     * Relation, damit die Linie wirklich den Kopf vom Inhalt trennt.
     */
    public function test_a_drawer_puts_its_head_above_a_divider_and_its_items_below(): void
    {
        $drawer = $this->freezer->drawers()->firstOrFail();

        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Zucchini']);
        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Erbsen']);

        $html = $this->page($this->owner)->html();

        // Nur die Karte der ersten Schublade ansehen.
        $card = substr($html, strpos($html, 'drawer-row'));
        $card = substr($card, 0, strpos($card, '</a>') + 4);

        // Der Kopf steht auf einer Linie: links das Icon, daneben der Name, fett.
        $this->assertMatchesRegularExpression(
            '/<span class="flex items-center gap-3">\s*'
                .'<span class="grid h-10 w-10[^"]*">\s*<svg.*?<\/svg>\s*<\/span>\s*'
                .'<span class="min-w-0 flex-1 truncate text-sm font-bold text-frost-900">'
                .preg_quote($drawer->name, '/').'<\/span>/s',
            $card,
            'Der Name der Schublade steht nicht fett neben ihrem Icon.'
        );

        // Unter dem Kopf trennt eine Haarlinie ihn von der Liste.
        $divider = strpos($card, '<span class="block border-t border-frost-100"></span>');
        $list = strpos($card, '<ul class="space-y-2 pl-13">');

        $this->assertNotFalse($divider, 'Zwischen dem Kopf der Schublade und ihrer Liste steht keine Trennlinie.');
        $this->assertNotFalse($list, 'Die Artikel der Schublade stehen nicht in einer Liste.');
        $this->assertLessThan($list, $divider, 'Die Trennlinie steht nicht über der Liste der Schublade.');

        // Und unter der Linie stehen die Namen - alphabetisch, nichts daneben.
        $this->assertSame(['Erbsen', 'Zucchini'], $this->namesIn($card, $drawer->name));
    }

    /**
     * Der Kopf einer Schublade steht auf dem hellen Grau des Feldes: ein Band
     * umfasst Icon und Namen, läuft über die volle Breite der Karte bis zu der
     * Haarlinie und endet dort. Darunter bleibt die Liste auf dem Weiß der
     * Karte, und die Karte selbst trägt kein Polster mehr ("gap-0 p-0"): das
     * Band und der Rumpf bringen ihre eigenen mit und halten zusammen die
     * Abstände, die die Karte vorher hatte. Das Icon hebt sich von dem Grau ab,
     * weil es auf der Tönung der App sitzt (brand-50, das Glyph in brand-600).
     */
    public function test_the_head_of_a_drawer_carries_the_grey_of_the_field(): void
    {
        $drawers = $this->freezer->drawers()->get();

        foreach ($drawers as $drawer) {
            InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Erbsen']);
        }

        $html = $this->page($this->owner)->html();

        // Das Band: das Grau des Feldes über die volle Breite ("block"), die
        // Ecken der Karte oben, und das Polster der Karte (1rem an den Seiten,
        // 0.875rem oben, 0.75rem bis zur Linie).
        $this->assertStringContainsString(
            '<span class="block rounded-t-control bg-frost-200 px-4 pb-3 pt-3.5">',
            $html
        );

        // Jede Schublade trägt dasselbe Band.
        $this->assertSame(
            $drawers->count(),
            substr_count($html, 'rounded-t-control bg-frost-200 px-4 pb-3 pt-3.5'),
            'Nicht jede Schublade steht auf demselben grauen Kopf.'
        );

        // Die Karte überlässt ihr Polster dem Band und dem Rumpf, sonst liefe
        // das Grau nicht bis an ihren Rand.
        $this->assertStringContainsString('class="drawer-row flex-col items-stretch gap-0 p-0"', $html);

        // Das Icon sitzt auf der Tönung der App statt auf dem Grau der Karte:
        // brand-50 hebt es von dem Band ab, das Glyph trägt brand-600 (in der
        // dunklen Ansicht brand-400 auf brand-950).
        $this->assertStringContainsString(
            'rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">',
            $html
        );

        // Unter der Haarlinie liegt weiterhin das Weiß der Karte, und der Rumpf
        // trägt dasselbe Polster an den Seiten wie das Band ("px-4"): so beginnen
        // die Artikelnamen weiterhin auf der Linie des Schubladennamens.
        $this->assertStringContainsString('<div class="px-4 pb-3.5 pt-3">', $html);
        $this->assertSame(
            substr_count($html, 'bg-frost-200 px-4'),
            substr_count($html, '<div class="px-4 pb-3.5 pt-3">'),
            'Band und Liste tragen nicht dasselbe Polster an den Seiten.'
        );

        // Und das Grau endet wirklich an der Linie: über der Haarlinie das Band,
        // darunter die Liste - und kein zweites graues Feld im Rumpf.
        $card = substr($html, strpos($html, 'drawer-row'));
        $card = substr($card, 0, strpos($card, '</a>') + 4);

        $band = strpos($card, 'bg-frost-200');
        $divider = strpos($card, '<span class="block border-t border-frost-100"></span>');
        $list = strpos($card, '<ul class="space-y-2 pl-13">');

        $this->assertNotFalse($band, 'Der Kopf der Schublade steht nicht auf dem Grau des Feldes.');
        $this->assertNotFalse($divider, 'Zwischen dem Kopf der Schublade und ihrer Liste steht keine Trennlinie.');
        $this->assertLessThan($divider, $band, 'Das graue Band steht nicht über der Trennlinie.');
        $this->assertLessThan($list, $divider, 'Die Trennlinie steht nicht über der Liste der Schublade.');
        $this->assertSame(
            1,
            substr_count($card, 'bg-frost-200'),
            'Das Grau des Feldes reicht über den Kopf hinaus in die Liste der Artikel.'
        );
    }

    /**
     * Der Inhalt einer Schublade beginnt auf derselben Linie wie ihr Name: der
     * Kopf stellt sein Icon (h-10 w-10 = 2.5rem) mit dem Abstand des Kopfes
     * (gap-3 = 0.75rem) neben den Namen, und die Liste trägt genau diese Strecke
     * als linken Einzug (pl-13 = 13 Schritte der Abstandsskala = 3.25rem). Die
     * Zahlen sind fest, also steht die Linie auf dem Smartphone wie auf dem
     * großen Bildschirm - und jede Schublade rückt gleich weit ein. Icon, Name
     * und Haarlinie bleiben dabei, wo sie sind.
     */
    public function test_the_items_of_a_drawer_start_on_the_line_of_its_name(): void
    {
        $drawers = $this->freezer->drawers()->get();

        foreach ($drawers as $drawer) {
            InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Erbsen']);
        }

        $html = $this->page($this->owner)->html();

        // Der Kopf der Schublade: das Icon in fester Breite, daneben der Name,
        // dazwischen der Abstand des Kopfes.
        $this->assertStringContainsString('<span class="flex items-center gap-3">', $html);
        $this->assertMatchesRegularExpression('/class="grid h-10 w-10[^"]*"/', $html);

        // Jede Schublade trägt denselben Einzug auf ihrer Liste.
        $this->assertSame(
            $drawers->count(),
            substr_count($html, '<ul class="space-y-2 pl-13">'),
            'Nicht jede Schublade rückt ihre Artikel gleich weit ein.'
        );

        // Und der Einzug ist genau Icon plus Abstand: 13 x 0.25rem = 2.5rem +
        // 0.75rem. Damit beginnt jeder Artikelname dort, wo der Name der
        // Schublade beginnt.
        preg_match('/<ul class="space-y-2 pl-(\d+)">/', $html, $offset);

        $this->assertSame(
            2.5 + 0.75,
            (int) ($offset[1] ?? 0) * 0.25,
            'Die Artikel beginnen nicht auf der Linie des Namens ihrer Schublade.'
        );
    }

    /**
     * Die Liste einer Schublade ist kein Standbild: sie wird bei jeder Ausgabe
     * neu aus der Relation gebaut und folgt damit jedem Anlegen, Umbenennen,
     * Verschieben und Löschen - und bleibt dabei alphabetisch. (Gearbeitet wird
     * in der Schublade selbst; die Startseite hat für Artikel keine Aktionen.)
     */
    public function test_the_list_of_a_drawer_follows_the_items(): void
    {
        $drawer = $this->freezer->drawers()->firstOrFail();
        $other = $this->freezer->drawers()->skip(1)->firstOrFail();

        $zucchini = InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Zucchini']);

        $page = $this->page($this->owner);

        $this->assertSame(['Zucchini'], $this->namesIn($page->html(), $drawer->name));

        // Ein neuer Artikel erscheint - und sortiert sich ein.
        $apple = InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Äpfel']);

        $this->assertSame(['Äpfel', 'Zucchini'], $this->namesIn($page->call('$refresh')->html(), $drawer->name));

        // Ein umbenannter Artikel wandert an seinen neuen Platz.
        $zucchini->update(['name' => 'Banane']);

        $this->assertSame(['Äpfel', 'Banane'], $this->namesIn($page->call('$refresh')->html(), $drawer->name));

        // Ein verschobener Artikel verlässt die Liste und steht in der anderen.
        // (Der Platz eines Artikels steht nicht in seinen füllbaren Feldern, er
        // wird über die Relation gesetzt - so wie die Schublade es selbst tut.)
        $apple->drawer()->associate($other);
        $apple->save();

        $html = $page->call('$refresh')->html();

        $this->assertSame(['Banane'], $this->namesIn($html, $drawer->name));
        $this->assertSame(['Äpfel'], $this->namesIn($html, $other->name));

        // Ein gelöschter Artikel verschwindet - die Liste bleibt übrig.
        $zucchini->delete();

        $this->assertSame([], $this->namesIn($page->call('$refresh')->html(), $drawer->name));
    }

    /**
     * Eine leere Schublade sagt das selbst - statt einer Liste steht dort der
     * Hinweis, den auch die Schublade selbst kennt.
     */
    public function test_an_empty_drawer_says_that_it_is_empty(): void
    {
        $this->page($this->owner)->assertSee('Diese Schublade ist noch leer.');
    }

    /**
     * Gelesen wird auf der Startseite nur: die Zeile einer Schublade ist ein
     * Link in die Schublade. Geändert wird ein Artikel dort, wo er liegt - hier
     * stellt also niemand eine Menge ein.
     */
    public function test_the_items_cannot_be_changed_on_the_start_page(): void
    {
        $drawer = $this->freezer->drawers()->firstOrFail();

        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Erbsen', 'quantity' => 2]);

        $this->page($this->owner)
            ->assertSeeHtml('href="'.route('drawers.show', $drawer).'"')
            ->assertDontSeeHtml('decrementQuantity')
            ->assertDontSeeHtml('incrementQuantity')
            ->assertDontSeeHtml('startEditingItem')
            ->assertDontSeeHtml('startMovingItem');
    }

    /**
     * Managing the drawers is the last thing on the start page - and it is called
     * "Schubladen bearbeiten" now.
     */
    public function test_the_drawer_button_sits_at_the_bottom_of_the_page(): void
    {
        $html = $this->page($this->owner)->html();

        $this->assertStringContainsString('Schubladen bearbeiten', $html);
        $this->assertStringNotContainsString('Gefrierschrank öffnen', $html);

        // Nothing but the drawers themselves comes before the button.
        $this->assertGreaterThan(
            strrpos($html, 'drawer-row'),
            strpos($html, 'Schubladen bearbeiten'),
        );
    }

    /**
     * Der Eintrag „Gefrierschrank hinzufügen“ im Menü des Feldes bringt das
     * Formular für einen weiteren Gefrierschrank hoch.
     */
    public function test_the_form_for_a_new_freezer_appears_from_the_menu(): void
    {
        $this->page($this->owner)
            ->assertSee('Gefrierschrank hinzufügen')
            ->assertSeeHtml('wire:click="startAddingFreezer"')
            ->call('startAddingFreezer')
            ->assertSet('addingFreezer', true)
            ->assertSee('Neuer Gefrierschrank');
    }

    public function test_adding_a_freezer_can_be_cancelled(): void
    {
        $this->page($this->owner)
            ->call('startAddingFreezer')
            ->set('newFreezerName', 'Truhe im Keller')
            ->call('cancelAddingFreezer')
            ->assertSet('addingFreezer', false)
            ->assertSet('newFreezerName', '')
            ->assertDontSee('Neuer Gefrierschrank');

        $this->assertSame(1, $this->owner->freezers()->count());
    }

    public function test_a_second_freezer_can_be_created_with_its_own_drawers(): void
    {
        $this->page($this->owner)
            ->call('startAddingFreezer')
            ->set('newFreezerName', 'Truhe im Keller')
            ->call('addFreezer')
            ->assertSet('addingFreezer', false)
            ->assertSet('newFreezerName', '')
            ->assertRedirect(route('freezer.closed'))
            ->assertSessionHas('status');

        $created = Freezer::query()->where('name', 'Truhe im Keller')->firstOrFail();

        $this->assertSame($this->owner->getKey(), $created->owner_id);
        $this->assertTrue($this->owner->isMemberOf($created));
        $this->assertSame(
            ['Schublade 1', 'Schublade 2', 'Schublade 3'],
            $created->drawers()->pluck('name')->all()
        );

        // The new freezer is empty and becomes the active one right away.
        $this->assertSame(0, $created->itemCount());
        $this->assertSame($created->getKey(), FreezerContext::id());
        $this->assertSame(2, $this->owner->freezers()->count());
    }

    public function test_a_member_can_create_a_freezer_of_their_own(): void
    {
        $member = $this->member();

        $this->page($member)
            ->set('newFreezerName', 'Eigene Truhe')
            ->call('addFreezer')
            ->assertRedirect(route('freezer.closed'));

        $own = $member->ownedFreezers()->where('name', 'Eigene Truhe')->firstOrFail();

        $this->assertTrue($member->isMemberOf($own));
        $this->assertSame($own->getKey(), FreezerContext::id());
    }

    public function test_creating_a_freezer_needs_a_name(): void
    {
        $this->page($this->owner)
            ->set('newFreezerName', '   ')
            ->call('addFreezer')
            ->assertHasErrors('newFreezerName');

        $this->assertSame(1, $this->owner->freezers()->count());
    }

    public function test_a_freezer_name_is_shortened_to_the_allowed_length(): void
    {
        $this->page($this->owner)
            ->set('newFreezerName', Str::repeat('a', Freezer::NAME_MAX_LENGTH + 20))
            ->call('addFreezer');

        $created = $this->owner->ownedFreezers()->latest('id')->firstOrFail();

        $this->assertSame(Freezer::NAME_MAX_LENGTH, mb_strlen($created->name));
    }

    public function test_the_owner_can_rename_a_freezer(): void
    {
        $this->page($this->owner)
            ->call('startRenaming', $this->freezer->getKey())
            ->assertSet('editingFreezerId', $this->freezer->getKey())
            ->assertSet('editingFreezerName', 'Gefrierschrank Küche')
            ->set('editingFreezerName', 'Kühltruhe')
            ->call('renameFreezer')
            ->assertRedirect(route('freezer.closed'))
            ->assertSessionHas('status');

        $this->assertSame('Kühltruhe', $this->freezer->refresh()->name);

        // Renaming does not touch the selection.
        $this->assertSame($this->freezer->getKey(), FreezerContext::id());
    }

    public function test_renaming_needs_a_name(): void
    {
        $this->page($this->owner)
            ->call('startRenaming', $this->freezer->getKey())
            ->set('editingFreezerName', ' ')
            ->call('renameFreezer')
            ->assertHasErrors('editingFreezerName');

        $this->assertSame('Gefrierschrank Küche', $this->freezer->refresh()->name);
    }

    public function test_renaming_can_be_cancelled(): void
    {
        $this->page($this->owner)
            ->call('startRenaming', $this->freezer->getKey())
            ->call('cancelRenaming')
            ->assertSet('editingFreezerId', null)
            ->assertSet('editingFreezerName', '');

        $this->assertSame('Gefrierschrank Küche', $this->freezer->refresh()->name);
    }

    public function test_a_member_may_not_rename_a_shared_freezer(): void
    {
        $member = $this->member();

        $this->page($member)
            ->call('startRenaming', $this->freezer->getKey())
            ->assertForbidden();

        $this->assertSame('Gefrierschrank Küche', $this->freezer->refresh()->name);
    }

    public function test_a_foreign_freezer_cannot_be_renamed(): void
    {
        $foreign = Freezer::factory()->create();

        $this->page($this->owner)
            ->call('startRenaming', $foreign->getKey())
            ->assertNotFound();

        $this->assertNotSame('Gefrierschrank Küche', $foreign->refresh()->name);
    }

    public function test_the_active_freezer_can_be_switched_on_the_freezer_page(): void
    {
        $shared = Freezer::factory()->sharedWith($this->owner)->create(['name' => 'Truhe im Keller']);

        $this->page($this->owner)
            ->call('selectFreezer', $shared->getKey())
            ->assertRedirect(route('freezer.closed'));

        $this->assertSame($shared->getKey(), FreezerContext::id());
        $this->assertTrue(FreezerContext::current($this->owner)->is($shared));
    }

    public function test_a_foreign_freezer_cannot_be_selected(): void
    {
        $foreign = Freezer::factory()->create();

        $this->page($this->owner)
            ->call('selectFreezer', $foreign->getKey())
            ->assertNotFound();

        $this->assertSame($this->freezer->getKey(), FreezerContext::id());
    }

    public function test_the_delete_entry_asks_through_the_dialog_first(): void
    {
        $this->page($this->owner)
            ->assertSeeHtml("name: 'freezer-delete-".$this->freezer->getKey()."'")
            ->assertSee('Gefrierschrank löschen?')
            ->assertSee('„Gefrierschrank Küche“ verschwindet mit allen Schubladen und Artikeln für immer.')
            ->assertSeeHtml('wire:click="deleteFreezer('.$this->freezer->getKey().')"');
    }

    public function test_the_owner_can_delete_a_freezer_with_its_content(): void
    {
        $drawer = $this->freezer->drawers()->firstOrFail();

        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey()]);

        $this->page($this->owner)
            ->call('deleteFreezer', $this->freezer->getKey())
            ->assertRedirect(route('freezer.closed'));

        $this->assertDatabaseMissing('freezers', ['id' => $this->freezer->getKey()]);
        $this->assertDatabaseMissing('drawers', ['freezer_id' => $this->freezer->getKey()]);
        $this->assertDatabaseCount('inventory_items', 0);

        // The page never stands there without a freezer, so a fresh one takes over.
        $fresh = $this->owner->freezers()->firstOrFail();

        $this->assertNotSame($this->freezer->getKey(), $fresh->getKey());
        $this->assertSame(Freezer::DEFAULT_DRAWER_COUNT, $fresh->drawers()->count());
        $this->assertTrue(FreezerContext::current($this->owner)->is($fresh));
    }

    public function test_deleting_the_active_freezer_switches_to_another_one(): void
    {
        $other = Freezer::factory()->withDrawers()->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Truhe im Keller',
        ]);

        FreezerContext::select($this->freezer);

        $this->page($this->owner)
            ->call('deleteFreezer', $this->freezer->getKey())
            ->assertRedirect(route('freezer.closed'));

        $this->assertDatabaseMissing('freezers', ['id' => $this->freezer->getKey()]);
        $this->assertTrue(FreezerContext::current($this->owner)->is($other));
    }

    public function test_a_member_may_not_delete_a_shared_freezer(): void
    {
        $member = $this->member();

        $this->page($member)
            ->call('deleteFreezer', $this->freezer->getKey())
            ->assertForbidden();

        $this->assertDatabaseHas('freezers', ['id' => $this->freezer->getKey()]);
    }

    public function test_a_foreign_freezer_cannot_be_deleted(): void
    {
        $foreign = Freezer::factory()->create();

        $this->page($this->owner)
            ->call('deleteFreezer', $foreign->getKey())
            ->assertNotFound();

        $this->assertDatabaseHas('freezers', ['id' => $foreign->getKey()]);
    }
}
