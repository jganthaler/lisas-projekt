<?php

namespace Tests\Feature;

use App\Livewire\InventoryOverview;
use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryOverviewTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Drawer $firstDrawer;

    private Drawer $secondDrawer;

    private Freezer $sharedFreezer;

    private Drawer $sharedDrawer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $freezer = Freezer::factory()->withDrawers(2)->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Gefrierschrank Küche',
        ]);

        [$this->firstDrawer, $this->secondDrawer] = $freezer->drawers()->get()->all();

        InventoryItem::factory()->fresh()->category(InventoryItem::CATEGORY_OBST)->create([
            'drawer_id' => $this->firstDrawer->getKey(),
            'name' => 'Äpfel',
        ]);

        InventoryItem::factory()->expired()->category(InventoryItem::CATEGORY_SONSTIGES)->create([
            'drawer_id' => $this->firstDrawer->getKey(),
            'name' => 'Brot',
        ]);

        InventoryItem::factory()->fresh()->category(InventoryItem::CATEGORY_OBST)->create([
            'drawer_id' => $this->secondDrawer->getKey(),
            'name' => 'Zitrone',
        ]);

        // A second freezer of the household that is shared with the owner.
        $this->sharedFreezer = Freezer::factory()
            ->withDrawers(1)
            ->sharedWith($this->owner)
            ->create(['name' => 'Truhe im Keller']);

        $this->sharedDrawer = $this->sharedFreezer->drawers()->firstOrFail();

        InventoryItem::factory()->fresh()->category(InventoryItem::CATEGORY_GEMUESE)->create([
            'drawer_id' => $this->sharedDrawer->getKey(),
            'name' => 'Pommes',
        ]);
    }

    /**
     * @return Testable<InventoryOverview>
     */
    private function page()
    {
        return Livewire::actingAs($this->owner)->test(InventoryOverview::class);
    }

    /**
     * Die Gruppen der Liste als Kategorie => Namen ihrer Artikel.
     *
     * @param  Testable<InventoryOverview>  $page
     * @return array<string, list<string>>
     */
    private function groupNames(Testable $page): array
    {
        return $page->viewData('groups')->mapWithKeys(fn (array $group): array => [
            $group['key'] => $group['items']->pluck('name')->all(),
        ])->all();
    }

    public function test_the_items_of_every_accessible_freezer_are_listed_alphabetically(): void
    {
        $page = $this->page();

        $this->assertSame(
            ['Äpfel', 'Brot', 'Pommes', 'Zitrone'],
            $page->viewData('items')->pluck('name')->all()
        );

        $this->assertSame(4, $page->viewData('totalCount'));
        $this->assertFalse($page->viewData('isFiltered'));

        // Nothing is narrowed down yet, so both freezers are offered.
        $this->assertNull($page->viewData('selectedFreezer'));
        $this->assertSame(
            ['Gefrierschrank Küche', 'Truhe im Keller'],
            $page->viewData('freezers')->pluck('name')->all()
        );
    }

    /**
     * The demo data of the seeder has to be alphabetical too: the items are
     * written through the models, so each one receives its "name_sort" key.
     */
    public function test_the_seeded_demo_inventory_is_listed_alphabetically(): void
    {
        $this->seed(DatabaseSeeder::class);

        $lisa = User::query()->where('email', 'lisa@example.com')->firstOrFail();

        $items = Livewire::actingAs($lisa)->test(InventoryOverview::class)->viewData('items');

        $this->assertSame(
            ['Äpfel', 'Beeren', 'Brot', 'Eiscreme', 'Erbsen', 'Gemüsemix', 'Hackfleisch', 'Lachs', 'Pizza', 'Pommes', 'Spinat', 'Suppe', 'Tomatensoße'],
            $items->pluck('name')->all()
        );

        // The demo is sorted into categories as well, so the new filter has
        // something to show right away.
        $this->assertNotContains(null, $items->pluck('category')->all());
    }

    public function test_items_can_be_searched(): void
    {
        $page = $this->page()->set('search', 'brot');

        $this->assertSame(['Brot'], $page->viewData('items')->pluck('name')->all());
        $this->assertTrue($page->viewData('isFiltered'));
    }

    public function test_items_can_be_filtered_by_freezer(): void
    {
        $page = $this->page()->call('filterByFreezer', (string) $this->sharedFreezer->getKey());

        $this->assertSame(['Pommes'], $page->viewData('items')->pluck('name')->all());
        $this->assertTrue($page->viewData('isFiltered'));
        $this->assertTrue($page->viewData('selectedFreezer')->is($this->sharedFreezer));
    }

    public function test_items_can_be_filtered_by_category(): void
    {
        $page = $this->page()->call('filterByCategory', InventoryItem::CATEGORY_OBST);

        $this->assertSame(['Äpfel', 'Zitrone'], $page->viewData('items')->pluck('name')->all());
        $this->assertSame(InventoryItem::CATEGORY_OBST, $page->viewData('selectedCategory'));
        $this->assertTrue($page->viewData('isFiltered'));
        $this->assertTrue($page->viewData('hasFilterSelection'));
    }

    /**
     * The category filter narrows down the list that the freezer filter already
     * produced - the two are not alternatives.
     */
    public function test_the_category_filter_can_be_combined_with_the_freezer_filter(): void
    {
        $page = $this->page()
            ->call('filterByFreezer', (string) $this->sharedFreezer->getKey())
            ->call('filterByCategory', InventoryItem::CATEGORY_OBST);

        // The shared freezer only holds vegetables, so both filters together find
        // nothing - while the category on its own does.
        $this->assertSame([], $page->viewData('items')->pluck('name')->all());

        $this->assertSame(
            ['Äpfel', 'Zitrone'],
            $this->page()->call('filterByCategory', InventoryItem::CATEGORY_OBST)
                ->viewData('items')->pluck('name')->all()
        );
    }

    public function test_an_unknown_category_means_every_category(): void
    {
        $page = $this->page()->call('filterByCategory', 'gibt-es-nicht');

        $this->assertSame('', $page->get('categoryFilter'));
        $this->assertNull($page->viewData('selectedCategory'));
        $this->assertFalse($page->viewData('hasFilterSelection'));
        $this->assertSame(4, $page->viewData('items')->count());
    }

    /**
     * Die Reihenfolge der Liste steht im Filter-Panel zur Wahl: Name (die
     * Voreinstellung), Kategorie oder MHD – „Nach MHD“ heißt: was zuerst
     * abläuft, steht oben.
     */
    public function test_the_list_can_be_sorted_by_name(): void
    {
        $page = $this->page()->call('sortBy', InventoryItem::SORT_ALPHABETICAL);

        $this->assertSame(
            ['Äpfel', 'Brot', 'Pommes', 'Zitrone'],
            $page->viewData('items')->pluck('name')->all()
        );
        $this->assertSame(InventoryItem::SORT_ALPHABETICAL, $page->get('sort'));
    }

    /**
     * Nach Kategorie heißt: in der Reihenfolge der Chips des Filters (siehe
     * InventoryItem::categoryLabels()) – Gemüse vor Obst vor Sonstiges.
     */
    public function test_the_list_can_be_sorted_by_category(): void
    {
        $page = $this->page()->call('sortBy', InventoryItem::SORT_CATEGORY);

        $this->assertSame(
            ['Pommes', 'Äpfel', 'Zitrone', 'Brot'],
            $page->viewData('items')->pluck('name')->all()
        );
    }

    /**
     * Ein Artikel ohne Kategorie hat in der Reihenfolge der Kategorien keinen
     * Platz: die Abfrage stellt ihn hinter „Sonstiges“ – in der Liste selbst
     * steht er unter „Sonstiges“ (siehe
     * test_an_item_without_a_category_is_listed_under_sonstiges).
     */
    public function test_an_item_without_a_category_closes_the_list_of_categories(): void
    {
        InventoryItem::factory()->fresh()->create([
            'drawer_id' => $this->firstDrawer->getKey(),
            'name' => 'Salz',
        ]);

        $this->assertSame(
            ['Pommes', 'Äpfel', 'Zitrone', 'Brot', 'Salz'],
            $this->page()->call('sortBy', InventoryItem::SORT_CATEGORY)
                ->viewData('items')->pluck('name')->all()
        );
    }

    /**
     * Nach Kategorie steht jede Kategorie als eigene Überschrift über ihren
     * Artikeln – in der Reihenfolge der Chips (siehe categoryLabels()), und
     * jede Kategorie für sich alphabetisch. Eine leere Kategorie bekommt keine
     * Überschrift: „Fleisch“ steht nicht über nichts.
     */
    public function test_every_category_becomes_a_heading_of_its_own(): void
    {
        $page = $this->page()->call('sortBy', InventoryItem::SORT_CATEGORY);

        $this->assertTrue($page->viewData('groupedByCategory'));

        $this->assertSame(
            [
                InventoryItem::CATEGORY_GEMUESE => ['Pommes'],
                InventoryItem::CATEGORY_OBST => ['Äpfel', 'Zitrone'],
                InventoryItem::CATEGORY_SONSTIGES => ['Brot'],
            ],
            $this->groupNames($page)
        );

        // Die Überschrift steht als solche in der Liste, mit der Zahl ihrer
        // Artikel daneben.
        $page->assertSeeHtml('<h3 class="flex items-center gap-2 px-1 text-base font-bold text-frost-900">')
            ->assertSee('Gemüse')
            ->assertSee('Obst')
            ->assertSee('Sonstiges')
            ->assertDontSee('Fleisch');
    }

    /**
     * Ein Artikel ohne Kategorie steht unter „Sonstiges“, alphabetisch zwischen
     * dessen Artikeln. Die Kategorie wird dafür nicht geschrieben – das Feld
     * bleibt leer –, und es entsteht auch keine weitere Kategorie: die Gruppen
     * sind die bekannten sieben.
     */
    public function test_an_item_without_a_category_is_listed_under_sonstiges(): void
    {
        $salz = InventoryItem::factory()->fresh()->create([
            'drawer_id' => $this->firstDrawer->getKey(),
            'name' => 'Salz',
        ]);

        $page = $this->page()->call('sortBy', InventoryItem::SORT_CATEGORY);

        $this->assertSame(
            [
                InventoryItem::CATEGORY_GEMUESE => ['Pommes'],
                InventoryItem::CATEGORY_OBST => ['Äpfel', 'Zitrone'],
                InventoryItem::CATEGORY_SONSTIGES => ['Brot', 'Salz'],
            ],
            $this->groupNames($page)
        );

        foreach (array_keys($this->groupNames($page)) as $key) {
            $this->assertContains($key, InventoryItem::categoryKeys());
        }

        $this->assertNull($salz->refresh()->category);
    }

    /**
     * Die Gruppen entstehen bei jedem Aufruf aus den Daten: ein neuer Artikel
     * steht in seiner Gruppe, ein zugeordneter wandert aus „Sonstiges“ in seine
     * Kategorie, und ein umbenannter rutscht innerhalb seiner Gruppe an seinen
     * alphabetischen Platz.
     */
    public function test_the_groups_follow_the_data(): void
    {
        $salz = InventoryItem::factory()->fresh()->create([
            'drawer_id' => $this->firstDrawer->getKey(),
            'name' => 'Salz',
        ]);

        $this->assertSame(
            [
                InventoryItem::CATEGORY_GEMUESE => ['Pommes'],
                InventoryItem::CATEGORY_OBST => ['Äpfel', 'Zitrone'],
                InventoryItem::CATEGORY_SONSTIGES => ['Brot', 'Salz'],
            ],
            $this->groupNames($this->page()->call('sortBy', InventoryItem::SORT_CATEGORY))
        );

        // Zugeordnet wird in der Schublade, nicht in dieser Liste - die
        // nächste Anfrage liest die Kategorie trotzdem.
        $salz->update(['category' => InventoryItem::CATEGORY_OBST]);

        $this->assertSame(
            [
                InventoryItem::CATEGORY_GEMUESE => ['Pommes'],
                InventoryItem::CATEGORY_OBST => ['Äpfel', 'Salz', 'Zitrone'],
                InventoryItem::CATEGORY_SONSTIGES => ['Brot'],
            ],
            $this->groupNames($this->page()->call('sortBy', InventoryItem::SORT_CATEGORY))
        );

        $salz->update(['name' => 'Aprikose']);

        $this->assertSame(
            ['Äpfel', 'Aprikose', 'Zitrone'],
            $this->groupNames($this->page()->call('sortBy', InventoryItem::SORT_CATEGORY))[InventoryItem::CATEGORY_OBST]
        );
    }

    /**
     * Gruppiert wird, was die Zeilen zeigen: ein Filter lässt nur die Gruppe
     * stehen, die er übrig lässt – die Überschriften der anderen fallen mit
     * ihren Artikeln weg.
     */
    public function test_a_filter_leaves_only_the_group_it_kept(): void
    {
        $page = $this->page()
            ->call('sortBy', InventoryItem::SORT_CATEGORY)
            ->call('filterByCategory', InventoryItem::CATEGORY_OBST);

        $this->assertSame(
            [InventoryItem::CATEGORY_OBST => ['Äpfel', 'Zitrone']],
            $this->groupNames($page)
        );

        $page->assertSee('Obst')->assertDontSee('Sonstiges');
    }

    /**
     * Nur die Reihenfolge nach Kategorie gruppiert: in jeder anderen bleibt die
     * Liste die eine, flache Liste, die sie immer war – „Sonstiges“ ist dann
     * eine Farbe, kein Abschnitt.
     */
    public function test_only_the_order_by_category_groups_the_list(): void
    {
        $page = $this->page();

        $this->assertFalse($page->viewData('groupedByCategory'));

        // Eine einzige Gruppe, ohne Überschrift, in der Reihenfolge der Abfrage.
        $this->assertSame(
            ['all' => ['Äpfel', 'Brot', 'Pommes', 'Zitrone']],
            $this->groupNames($page)
        );

        $page->assertDontSee('Sonstiges')->assertDontSee('Gemüse')->assertDontSee('Obst');
    }

    /**
     * Die Reihenfolge „Nach MHD“ stellt oben hin, was zuerst abläuft: der
     * abgelaufene Artikel (Brot), dann der, der morgen abläuft (Hackfleisch),
     * dann die frischen – die untereinander alphabetisch (Äpfel, Pommes und
     * Zitrone laufen alle in 60 Tagen ab).
     */
    public function test_the_list_can_be_sorted_by_best_before_date_ascending(): void
    {
        InventoryItem::factory()->expiringSoon(1)->create([
            'drawer_id' => $this->secondDrawer->getKey(),
            'name' => 'Hackfleisch',
        ]);

        $this->assertSame(
            ['Brot', 'Hackfleisch', 'Äpfel', 'Pommes', 'Zitrone'],
            $this->page()->call('sortBy', InventoryItem::SORT_BEST_BEFORE_ASC)
                ->viewData('items')->pluck('name')->all()
        );
    }

    /**
     * „Kein MHD“ ist kein Datum, das dringend werden könnte: Artikel ohne MHD
     * schließen die Liste.
     */
    public function test_items_without_a_best_before_date_close_the_list(): void
    {
        InventoryItem::factory()->withoutBestBefore()->create([
            'drawer_id' => $this->firstDrawer->getKey(),
            'name' => 'Salz',
        ]);

        $page = $this->page()->call('sortBy', InventoryItem::SORT_BEST_BEFORE_ASC);

        $this->assertSame(5, $page->viewData('items')->count());
        $this->assertSame('Salz', $page->viewData('items')->last()->name);
    }

    /**
     * Die Reihenfolge „Nach MHD“ gruppiert die Liste in die Fenster der
     * restlichen Haltbarkeit: was abgelaufen ist oder im nächsten Monat
     * abläuft, was in den nächsten sechs Monaten abläuft, was länger hält –
     * und Artikel ohne MHD schließen die Liste. Die Vorgabe kennt ein
     * abgelaufenes Brot und drei frische Artikel, die alle am selben Tag
     * ablaufen; der Lachs hält 300 Tage, das Salz hat kein MHD.
     */
    public function test_the_order_by_best_before_date_groups_the_remaining_shelf_life(): void
    {
        InventoryItem::factory()->fresh(300)->create([
            'drawer_id' => $this->secondDrawer->getKey(),
            'name' => 'Lachs',
        ]);

        InventoryItem::factory()->withoutBestBefore()->create([
            'drawer_id' => $this->firstDrawer->getKey(),
            'name' => 'Salz',
        ]);

        $page = $this->page()->call('sortBy', InventoryItem::SORT_BEST_BEFORE_ASC);

        $this->assertTrue($page->viewData('groupedByBestBefore'));

        $this->assertSame(
            [
                InventoryItem::BEST_BEFORE_GROUP_MONTH => ['Brot'],
                InventoryItem::BEST_BEFORE_GROUP_SIX_MONTHS => ['Äpfel', 'Pommes', 'Zitrone'],
                InventoryItem::BEST_BEFORE_GROUP_LONGER => ['Lachs'],
                InventoryItem::BEST_BEFORE_GROUP_NONE => ['Salz'],
            ],
            $this->groupNames($page)
        );

        // Jede Gruppe ist eine Gruppe der Reihenfolge nach MHD - keine
        // Kategorie hat sich untergemischt.
        foreach (array_keys($this->groupNames($page)) as $key) {
            $this->assertContains($key, InventoryItem::bestBeforeGroupKeys());
        }

        // Die Überschriften stehen als solche in der Liste, in der Reihenfolge
        // der Fenster, in der Gestalt der Abschnitte der MHD-Warnliste.
        $page->assertSeeHtml('<h3 class="flex items-center gap-2 px-1 text-base font-bold text-frost-900">')
            ->assertSeeHtmlInOrder([
                'Aufzubrauchen innerhalb 1 Monats',
                'Aufzubrauchen innerhalb 6 Monate',
                'Noch länger haltbar',
                'Kein MHD hinterlegt',
            ]);
    }

    /**
     * In einem Fenster entscheidet das Datum: „Banane“ läuft vor „Ananas“ ab
     * und steht darum oben, obwohl der Name später im Alphabet steht - der Name
     * entscheidet erst, wenn zwei Artikel am selben Tag ablaufen.
     */
    public function test_the_items_of_a_window_follow_their_date(): void
    {
        InventoryItem::factory()->fresh(100)->create([
            'drawer_id' => $this->secondDrawer->getKey(),
            'name' => 'Ananas',
        ]);

        InventoryItem::factory()->fresh(90)->create([
            'drawer_id' => $this->secondDrawer->getKey(),
            'name' => 'Banane',
        ]);

        $this->assertSame(
            [
                InventoryItem::BEST_BEFORE_GROUP_MONTH => ['Brot'],
                InventoryItem::BEST_BEFORE_GROUP_SIX_MONTHS => ['Äpfel', 'Pommes', 'Zitrone', 'Banane', 'Ananas'],
            ],
            $this->groupNames($this->page()->call('sortBy', InventoryItem::SORT_BEST_BEFORE_ASC))
        );
    }

    /**
     * Die Fenster entstehen bei jedem Aufruf aus den Daten: ein nachgetragenes
     * MHD bringt den Artikel in sein Fenster, ein entferntes schließt die Liste
     * – ohne dass der Artikel eine Kategorie bekäme.
     */
    public function test_the_shelf_life_windows_follow_the_data(): void
    {
        $apfel = InventoryItem::query()->where('name', 'Äpfel')->firstOrFail();

        $this->assertSame(
            [
                InventoryItem::BEST_BEFORE_GROUP_MONTH => ['Brot'],
                InventoryItem::BEST_BEFORE_GROUP_SIX_MONTHS => ['Äpfel', 'Pommes', 'Zitrone'],
            ],
            $this->groupNames($this->page()->call('sortBy', InventoryItem::SORT_BEST_BEFORE_ASC))
        );

        $apfel->update(['best_before' => Carbon::today()->addMonthsNoOverflow(8)]);

        $this->assertSame(
            [
                InventoryItem::BEST_BEFORE_GROUP_MONTH => ['Brot'],
                InventoryItem::BEST_BEFORE_GROUP_SIX_MONTHS => ['Pommes', 'Zitrone'],
                InventoryItem::BEST_BEFORE_GROUP_LONGER => ['Äpfel'],
            ],
            $this->groupNames($this->page()->call('sortBy', InventoryItem::SORT_BEST_BEFORE_ASC))
        );

        $apfel->update(['best_before' => null]);

        $this->assertSame(
            [
                InventoryItem::BEST_BEFORE_GROUP_MONTH => ['Brot'],
                InventoryItem::BEST_BEFORE_GROUP_SIX_MONTHS => ['Pommes', 'Zitrone'],
                InventoryItem::BEST_BEFORE_GROUP_NONE => ['Äpfel'],
            ],
            $this->groupNames($this->page()->call('sortBy', InventoryItem::SORT_BEST_BEFORE_ASC))
        );
    }

    /**
     * Ein Fenster ohne Artikel bekommt keine Überschrift: „Noch länger haltbar“
     * steht nicht über nichts. Ein Filter lässt nur die Fenster stehen, die er
     * übrig lässt – die Überschriften der anderen fallen mit ihren Artikeln
     * weg, wie bei den Kategorien.
     */
    public function test_a_window_without_items_gets_no_heading(): void
    {
        $page = $this->page()->call('sortBy', InventoryItem::SORT_BEST_BEFORE_ASC);

        $this->assertSame(
            [
                InventoryItem::BEST_BEFORE_GROUP_MONTH => ['Brot'],
                InventoryItem::BEST_BEFORE_GROUP_SIX_MONTHS => ['Äpfel', 'Pommes', 'Zitrone'],
            ],
            $this->groupNames($page)
        );

        $page->assertDontSee('Noch länger haltbar')->assertDontSee('Kein MHD hinterlegt');

        $filtered = $this->page()
            ->call('sortBy', InventoryItem::SORT_BEST_BEFORE_ASC)
            ->call('filterByCategory', InventoryItem::CATEGORY_OBST);

        $this->assertSame(
            [InventoryItem::BEST_BEFORE_GROUP_SIX_MONTHS => ['Äpfel', 'Zitrone']],
            $this->groupNames($filtered)
        );

        $filtered->assertSee('Aufzubrauchen innerhalb 6 Monate')
            ->assertDontSee('Aufzubrauchen innerhalb 1 Monats');
    }

    /**
     * Nur die Reihenfolge nach MHD gruppiert in Haltbarkeitsfenster: in jeder
     * anderen – auch in der nach Kategorie – bleibt die Liste die eine, flache
     * Liste, die sie immer war.
     */
    public function test_only_the_order_by_best_before_date_groups_by_shelf_life(): void
    {
        $page = $this->page();

        $this->assertFalse($page->viewData('groupedByBestBefore'));

        $this->assertSame(
            ['all' => ['Äpfel', 'Brot', 'Pommes', 'Zitrone']],
            $this->groupNames($page)
        );

        $page->assertDontSee('Aufzubrauchen innerhalb 1 Monats')
            ->assertDontSee('Kein MHD hinterlegt');

        $byCategory = $this->page()->call('sortBy', InventoryItem::SORT_CATEGORY);

        $this->assertFalse($byCategory->viewData('groupedByBestBefore'));
        $byCategory->assertDontSee('Aufzubrauchen innerhalb 1 Monats');
    }

    public function test_an_unknown_sort_keeps_the_list_alphabetical(): void
    {
        $page = $this->page()->call('sortBy', 'nach-dem-zufall');

        $this->assertSame(InventoryItem::SORT_ALPHABETICAL, $page->get('sort'));
        $this->assertSame(
            ['Äpfel', 'Brot', 'Pommes', 'Zitrone'],
            $page->viewData('items')->pluck('name')->all()
        );
    }

    /**
     * Sortieren schränkt nichts ein: die Liste bleibt vollständig, „isFiltered“
     * bleibt falsch und der Punkt am Filter-Icon bleibt dunkel – er zeigt
     * Filter an, nicht die Reihenfolge.
     */
    public function test_sorting_narrows_nothing_down(): void
    {
        $page = $this->page()->call('sortBy', InventoryItem::SORT_BEST_BEFORE_ASC);

        $this->assertSame(4, $page->viewData('items')->count());
        $this->assertFalse($page->viewData('isFiltered'));
        $this->assertFalse($page->viewData('hasFilterSelection'));
    }

    /**
     * Das Panel bietet jede Reihenfolge an; die gewählte trägt den Knopf der
     * aktiven Wahl. „MHD: später zuerst“ steht dort nicht mehr: das Inventar
     * kennt nur die Reihenfolge, die oben hinstellt, was zuerst abläuft.
     */
    public function test_the_filter_panel_offers_every_sort(): void
    {
        $page = $this->page()->call('toggleFilters');

        $page->assertSee('Sortierung')
            ->assertSee('Name (A–Z)')
            ->assertSee('Nach MHD')
            ->assertSeeHtml('wire:click="sortBy(\''.InventoryItem::SORT_CATEGORY.'\')"');

        $page->assertDontSee('MHD: bald zuerst')
            ->assertDontSee('MHD: später zuerst')
            ->assertDontSeeHtml('best_before_desc');
    }

    /**
     * Die drei Felder sind gleich breit: sie stehen in einem Raster aus drei
     * gleich breiten Spalten über die ganze Breite des Panels, jedes Feld füllt
     * seine Spalte und stellt sein Etikett mittig – so ist „Name (A–Z)“ genau so
     * breit wie „Nach MHD“, unabhängig von der Länge des Wortes. Eine Reihe
     * flexibler Felder machte jedes so breit wie sein Wort.
     */
    public function test_the_sorts_stand_in_three_equal_columns_with_centred_labels(): void
    {
        $page = $this->page()->call('toggleFilters');

        $this->assertSame(
            1,
            substr_count($page->html(), 'grid grid-cols-3 gap-1.5'),
            'Die drei Reihenfolgen stehen nicht in drei gleich breiten Spalten.'
        );

        $this->assertSame(
            count(InventoryItem::sortKeys()),
            substr_count($page->html(), 'w-full justify-center whitespace-normal px-2 py-1.5 text-center'),
            'Nicht jedes Feld füllt seine Spalte und stellt sein Etikett mittig.'
        );
    }

    /**
     * „Alle Artikel zeigen“ nimmt Suche und Filter zurück, nicht die gewählte
     * Reihenfolge: die gehört dem Leser, nicht der Suche.
     */
    public function test_clearing_the_filters_keeps_the_chosen_order(): void
    {
        $page = $this->page()
            ->call('sortBy', InventoryItem::SORT_CATEGORY)
            ->set('search', 'gibt-es-nicht')
            ->call('clearFilters');

        $this->assertSame(InventoryItem::SORT_CATEGORY, $page->get('sort'));
    }

    /**
     * The panel offers every category plus the way back to the full list.
     */
    public function test_the_filter_panel_offers_every_category(): void
    {
        $page = $this->page()->call('toggleFilters');

        foreach (['Fleisch', 'Fisch', 'Gemüse', 'Suppe', 'Soßen', 'Obst', 'Sonstiges'] as $label) {
            $page->assertSee($label);
        }

        $page->assertSee('Alle Kategorien')
            ->assertSeeHtml('wire:click="filterByCategory(\''.InventoryItem::CATEGORY_OBST.'\')"');
    }

    public function test_a_foreign_freezer_cannot_be_filtered(): void
    {
        $foreign = Freezer::factory()->withDrawers(1)->create();
        InventoryItem::factory()->create([
            'drawer_id' => $foreign->drawers()->firstOrFail()->getKey(),
            'name' => 'Fremde Erbsen',
        ]);

        $page = $this->page()->call('filterByFreezer', (string) $foreign->getKey());

        // An unknown freezer means "all freezers" - but never somebody else's.
        $this->assertNull($page->viewData('selectedFreezer'));
        $this->assertSame(
            ['Äpfel', 'Brot', 'Pommes', 'Zitrone'],
            $page->viewData('items')->pluck('name')->all()
        );
        $page->assertDontSee('Fremde Erbsen');
    }

    public function test_a_search_without_a_hit_shows_the_empty_state(): void
    {
        $this->page()
            ->set('search', 'gibt-es-nicht')
            ->assertSee('Dazu wurde nichts gefunden.');
    }

    /**
     * A list without a hit is no dead end: the way back to the whole inventory
     * stands right next to the message. It takes the search with it - that is
     * what found nothing in the first place.
     */
    public function test_a_search_without_a_hit_offers_the_way_back(): void
    {
        $page = $this->page()->set('search', 'gibt-es-nicht');

        $page->assertSee('Alle Artikel zeigen')
            // Without a hit there is nothing to open a drawer for.
            ->assertDontSeeHtml('?item=');

        $page->call('clearFilters')
            ->assertSet('search', '')
            ->assertSee('Pommes');
    }

    /**
     * An inventory without items leads to the freezer: that is where the first
     * item is entered, and a list that only says "empty" would leave the user
     * standing there.
     */
    public function test_an_empty_inventory_leads_to_the_freezer(): void
    {
        $user = User::factory()->create();

        Freezer::factory()->withDrawers(2)->create(['owner_id' => $user->getKey()]);

        Livewire::actingAs($user)->test(InventoryOverview::class)
            ->assertSee('Das Inventar ist noch leer.')
            ->assertSee('Zum Gefrierschrank')
            ->assertSeeHtml('href="'.route('freezer.closed').'"');
    }

    /**
     * Der Ort einer Zeile ist reiner Text: Gefrierschrank und Schublade stehen
     * mit Komma nebeneinander, in derselben Schrift und Farbe und ohne Link –
     * der Name der Schublade ist kein Weg mehr in sie (siehe
     * resources/views/components/item-row.blade.php). In die Schublade führt
     * weiterhin das Menü der Zeile („⋮“, siehe
     * test_every_row_carries_its_own_menu).
     */
    public function test_the_place_of_the_row_is_plain_text(): void
    {
        $this->page()->assertSeeHtml(
            '<p class="mt-0.5 truncate text-xs text-frost-500">'
            .$this->sharedFreezer->name.', '.$this->sharedDrawer->name.'</p>'
        );
    }

    public function test_the_filters_can_be_reset(): void
    {
        $page = $this->page()
            ->set('search', 'brot')
            ->set('freezerFilter', (string) $this->sharedFreezer->getKey())
            ->set('categoryFilter', InventoryItem::CATEGORY_SONSTIGES)
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('freezerFilter', '')
            ->assertSet('categoryFilter', '');

        $this->assertSame(4, $page->viewData('items')->count());
        $this->assertFalse($page->viewData('isFiltered'));
    }

    /**
     * The freezer buttons live behind the filter icon, so the search field is
     * the only thing on the page until somebody asks for the filter.
     */
    public function test_the_filter_button_reveals_the_freezer_choice(): void
    {
        $page = $this->page()
            ->assertSet('showFilters', false)
            ->assertDontSeeHtml('wire:click="filterByFreezer');

        $page->call('toggleFilters')
            ->assertSet('showFilters', true)
            ->assertSeeHtml('wire:click="filterByFreezer(\''.$this->sharedFreezer->getKey().'\')"');

        $page->call('toggleFilters')->assertSet('showFilters', false);
    }

    /**
     * The button is a square: as wide as it is high ("h-11 w-11", the square
     * size of the app - the tile of the freezer on the home page carries it as
     * well) and centred beside the search field, which is a little taller than
     * the square. Without "self-center" the field would stretch it into a
     * rectangle, because a flex row stretches its items by default.
     */
    public function test_the_filter_button_stands_as_a_square_next_to_the_search_field(): void
    {
        $this->page()->assertSeeHtml('class="btn btn-secondary btn-icon relative h-11 w-11 shrink-0 self-center"');
    }

    /**
     * The drawer of an item is shown in its row, but the inventory is never
     * filtered by drawer - the freezer is the only filter.
     */
    public function test_the_inventory_cannot_be_filtered_by_drawer(): void
    {
        $page = $this->page()->call('toggleFilters');

        $page->assertSee('Gefrierschrank')
            ->assertDontSee('Alle Schubladen')
            ->assertDontSeeHtml('$set(');
    }

    /**
     * With a single freezer there is nothing to choose, so the panel says so
     * instead of offering a filter that could never change the list.
     */
    public function test_the_filter_panel_says_when_there_is_nothing_to_filter(): void
    {
        $user = User::factory()->create();
        Freezer::factory()->withDrawers(2)->create(['owner_id' => $user->getKey()]);

        Livewire::actingAs($user)->test(InventoryOverview::class)
            ->call('toggleFilters')
            ->assertSee('Nur ein Gefrierschrank')
            ->assertDontSeeHtml('wire:click="filterByFreezer');
    }

    /**
     * Picking a freezer keeps the panel open, so it is obvious which filter is
     * active and it can be dropped again right away.
     */
    public function test_picking_a_filter_keeps_the_panel_open(): void
    {
        $page = $this->page()
            ->call('toggleFilters')
            ->call('filterByFreezer', (string) $this->sharedFreezer->getKey());

        $this->assertTrue($page->get('showFilters'));
        $this->assertTrue($page->viewData('hasFilterSelection'));
        $this->assertSame(['Pommes'], $page->viewData('items')->pluck('name')->all());
    }

    public function test_the_filter_selection_can_be_cleared_without_losing_the_search(): void
    {
        $page = $this->page()
            ->set('search', 'o')
            ->call('filterByFreezer', (string) $this->sharedFreezer->getKey())
            ->call('filterByCategory', InventoryItem::CATEGORY_GEMUESE)
            ->call('clearFilterSelection')
            ->assertSet('freezerFilter', '')
            ->assertSet('categoryFilter', '')
            ->assertSet('search', 'o');

        $this->assertNull($page->viewData('selectedFreezer'));
        $this->assertNull($page->viewData('selectedCategory'));
        $this->assertFalse($page->viewData('hasFilterSelection'));
    }

    /**
     * Ein Artikel ohne MHD lässt die Stelle des Datums nicht leer: dort steht
     * „Kein MHD“ (items.mhd.none) statt eines Etiketts ohne Datum. Zustands-Badge
     * („ohne MHD“) und relativer Zähler bleiben weg – es gibt nichts, wovor
     * gewarnt werden könnte (siehe resources/views/components/item-row.blade.php).
     */
    public function test_an_item_without_a_best_before_date_says_so(): void
    {
        InventoryItem::factory()->withoutBestBefore()->create([
            'drawer_id' => $this->firstDrawer->getKey(),
            'name' => 'Kräuter',
        ]);

        $this->page()
            ->set('search', 'Kräuter')
            // Die zweite Zeile steht dort, wo sonst das Datum steht: zwischen
            // dem Namen und dem Ort.
            ->assertSeeHtmlInOrder([
                'Kräuter',
                '<span>Kein MHD</span>',
                $this->firstDrawer->freezer->name.', ',
            ])
            // Kein Etikett ohne Datum und kein Zustand, der nichts sagt.
            ->assertDontSee('MHD:')
            ->assertDontSee('ohne MHD');
    }

    /**
     * Ein frischer Artikel zeigt im Inventar nur sein Datum: "frisch" sagt
     * nichts, und der relative Zähler ("MHD in 60 Tagen") steht in keiner Zeile
     * der App (siehe resources/views/components/item-row.blade.php).
     */
    public function test_a_fresh_item_shows_only_its_date(): void
    {
        $page = $this->page()->set('search', 'Zitrone');

        $item = InventoryItem::query()->firstWhere('name', 'Zitrone');

        $page->assertSee('Zitrone')
            ->assertSee($item->formattedBestBefore())
            ->assertDontSee($item->stateLabel())
            ->assertDontSee($item->bestBeforeLabel());
    }

    /**
     * Ein abgelaufener Artikel behält seine Warnung - sie ist das Einzige, was
     * eine Zeile neben dem Datum sagen darf.
     */
    public function test_an_expired_item_keeps_its_warning(): void
    {
        $page = $this->page()->set('search', 'Brot');

        $item = InventoryItem::query()->firstWhere('name', 'Brot');

        $page->assertSee('Brot')
            ->assertSee($item->stateLabel())
            ->assertSee($item->formattedBestBefore())
            ->assertDontSee($item->bestBeforeLabel());
    }

    /**
     * Die Zeile des Inventars ist weiß – wie jede Zeile der App: die Kategorie
     * eines Artikels dient dem Filter (siehe InventoryOverview), sie ist keine
     * Farbe der Zeile.
     */
    public function test_every_row_keeps_the_white_card(): void
    {
        foreach ([InventoryItem::CATEGORY_FLEISCH, InventoryItem::CATEGORY_OBST] as $category) {
            InventoryItem::factory()->fresh()->category($category)->create([
                'drawer_id' => $this->firstDrawer->getKey(),
                'name' => 'Artikel '.$category,
            ]);
        }

        // Brot ist „Sonstiges“, Salz hat gar keine Kategorie.
        InventoryItem::factory()->fresh()->create([
            'drawer_id' => $this->secondDrawer->getKey(),
            'name' => 'Salz',
        ]);

        $this->page()
            ->assertSeeHtml('class="card p-3"')
            ->assertSee('Artikel fleisch')
            ->assertSee('Artikel obst')
            ->assertSee('Salz')
            ->assertDontSeeHtml('bg-cat-');
    }

    /**
     * Drei Zeilen je Artikel: der Name, darunter „MHD: <Datum>“ mit dem Etikett
     * des Feldes und darunter der Ort – Gefrierschrank und Schublade mit Komma
     * (siehe resources/views/components/item-row.blade.php).
     */
    public function test_the_row_names_the_item_its_mhd_and_its_place(): void
    {
        $item = InventoryItem::query()->firstWhere('name', 'Pommes');

        $this->page()
            ->set('search', 'Pommes')
            ->assertSeeHtmlInOrder([
                'Pommes',
                'MHD: '.$item->formattedBestBefore(),
                $this->sharedFreezer->name.', ',
            ]);
    }

    public function test_an_item_can_be_deleted_from_the_overview(): void
    {
        $item = InventoryItem::query()->firstWhere('name', 'Brot');

        $this->page()
            ->call('deleteItem', $item->getKey())
            ->assertSet('itemFeedback', 'Artikel gelöscht.');

        $this->assertDatabaseMissing('inventory_items', ['id' => $item->getKey()]);
    }

    public function test_the_overview_asks_before_it_deletes_an_item(): void
    {
        $item = InventoryItem::query()->firstWhere('name', 'Brot');

        $this->page()
            ->assertSeeHtml("name: 'item-delete-".$item->getKey()."'")
            ->assertSee('Artikel löschen?')
            ->assertSee('Möchtest du „Brot“ wirklich löschen?')
            ->assertSeeHtml('wire:click="deleteItem('.$item->getKey().')"');

        $this->assertDatabaseHas('inventory_items', ['id' => $item->getKey()]);
    }

    public function test_the_quantity_can_be_changed_from_the_overview(): void
    {
        $item = InventoryItem::query()->firstWhere('name', 'Zitrone');
        $original = $item->quantity;

        $page = $this->page()->call('incrementQuantity', $item->getKey());

        $this->assertSame($original + 1, $item->refresh()->quantity);

        $page->call('decrementQuantity', $item->getKey());

        $this->assertSame($original, $item->refresh()->quantity);
    }

    /**
     * Bearbeiten, Löschen und Verschieben stehen hinter dem Menü ihrer Zeile
     * („⋮“, siehe resources/views/components/menu.blade.php). Bearbeiten und
     * Verschieben sind die Funktionen der Schublade: die Zeile führt mit dem
     * Artikel hinein – „?item=<id>“ öffnet sein Formular, „&move=1“ daneben sein
     * Verschieben-Formular (siehe DrawerPage::mount()) –, das Löschen fragt
     * weiterhin im Modal der App nach.
     */
    public function test_every_row_carries_its_own_menu(): void
    {
        $item = InventoryItem::query()->firstWhere('name', 'Brot');

        $this->page()
            ->set('search', 'Brot')
            // Das Menü sitzt in der Zeile und nennt sie in seinem Namen ...
            ->assertSeeHtml('aria-haspopup="menu"')
            ->assertSeeHtml('aria-label="Weitere Aktionen für „Brot“"')
            ->assertSee('Bearbeiten')
            ->assertSee('Löschen')
            ->assertSee('Verschieben')
            // ... und jede der drei Aktionen gehört zu genau diesem Artikel.
            ->assertSeeHtml('href="'.route('drawers.show', $this->firstDrawer).'?item='.$item->getKey().'"')
            ->assertSeeHtml('href="'.route('drawers.show', $this->firstDrawer).'?item='.$item->getKey().'&amp;move=1"')
            ->assertSeeHtml("name: 'item-delete-".$item->getKey()."'");
    }
}
