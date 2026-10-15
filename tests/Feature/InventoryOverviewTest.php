<?php

namespace Tests\Feature;

use App\Livewire\InventoryOverview;
use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ['Äpfel', 'Beeren', 'Brot', 'Eiscreme', 'Erbsen', 'Gemüsemix', 'Hackfleisch', 'Lachs', 'Pizza', 'Pommes', 'Spinat', 'Suppe'],
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
     * The panel offers every category plus the way back to the full list.
     */
    public function test_the_filter_panel_offers_every_category(): void
    {
        $page = $this->page()->call('toggleFilters');

        foreach (['Fleisch', 'Fisch', 'Gemüse', 'Suppe', 'Obst', 'Sonstiges'] as $label) {
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

    public function test_an_item_without_a_best_before_date_shows_no_mhd_information(): void
    {
        InventoryItem::factory()->withoutBestBefore()->create([
            'drawer_id' => $this->firstDrawer->getKey(),
            'name' => 'Kräuter',
        ]);

        $this->page()
            ->assertSee('Kräuter')
            ->assertDontSee('ohne MHD')
            ->assertDontSee('kein MHD');
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

        $page = $this->page()->call('confirmDelete', $item->getKey());

        $page->assertSet('confirmingItemId', $item->getKey())
            ->assertSee('Möchtest du „Brot“ wirklich löschen?')
            // While the question is open the trash button steps aside.
            ->assertDontSeeHtml('confirmDelete('.$item->getKey().')');

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
}
