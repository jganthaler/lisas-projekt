<?php

namespace Tests\Feature;

use App\Livewire\ExpiringItems;
use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The MHD watch list with a frozen clock, so the states are deterministic.
 */
class ExpiringItemsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Drawer $drawer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-15 08:00:00');

        $this->owner = User::factory()->create();

        $freezer = Freezer::factory()->withDrawers(1)->create(['owner_id' => $this->owner->getKey()]);

        $this->drawer = $freezer->drawers()->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function item(string $name, ?string $bestBefore): InventoryItem
    {
        return InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => $name,
            'best_before' => $bestBefore,
        ]);
    }

    /**
     * @return Testable<ExpiringItems>
     */
    private function page()
    {
        return Livewire::actingAs($this->owner)->test(ExpiringItems::class);
    }

    public function test_expired_and_expiring_items_are_grouped(): void
    {
        $this->item('Brot', '2026-06-10');
        $this->item('Heute-Suppe', '2026-06-15');
        $this->item('Spinat', '2026-06-20');
        $this->item('Grenzfall', '2026-06-22');
        $this->item('Zu weit', '2026-06-23');
        $this->item('Erbsen', '2026-07-01');
        $this->item('Beeren', null);

        $page = $this->page();

        $this->assertSame(['Brot'], $page->viewData('expired')->pluck('name')->all());
        $this->assertSame(
            ['Heute-Suppe', 'Spinat', 'Grenzfall'],
            $page->viewData('expiringSoon')->pluck('name')->all()
        );
        $this->assertSame(InventoryItem::WARNING_DAYS, $page->viewData('warningDays'));
    }

    /**
     * Die Warnliste lebt vom relativen Zähler ("MHD in 2 Tagen") und nennt
     * weiterhin Zustand, Zähler und Datum - nur das Inventar kürzt die Zeile
     * (siehe resources/views/components/item-row.blade.php).
     */
    public function test_the_watch_list_keeps_the_relative_mhd(): void
    {
        $item = $this->item('Erbsen', '2026-06-17');

        $this->page()
            ->assertSee($item->stateLabel())
            ->assertSee($item->bestBeforeLabel())
            ->assertSee($item->formattedBestBefore());
    }

    /**
     * Auch die Warnliste bleibt weiß: die Farbe der Kategorie gehört dem
     * Inventar (siehe resources/views/components/item-row.blade.php).
     */
    public function test_the_watch_list_keeps_the_white_card(): void
    {
        InventoryItem::factory()->expiringSoon(2)->category(InventoryItem::CATEGORY_GEMUESE)->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
        ]);

        $this->page()
            ->assertSee('Erbsen')
            ->assertDontSeeHtml('bg-cat-');
    }

    /**
     * Die Zeile der Warnliste führt in die Schublade des Artikels und öffnet dort
     * gleich sein Formular: aufbrauchen heißt oft auch, das MHD zu ändern (siehe
     * DrawerPage::mount()).
     */
    public function test_the_row_leads_into_the_drawer_of_the_item(): void
    {
        $item = $this->item('Erbsen', '2026-06-17');

        $this->page()->assertSeeHtml(
            'href="'.route('drawers.show', $this->drawer).'?item='.$item->getKey().'"'
        );
    }

    /**
     * The watch list is not tied to the freezer that happens to be selected on
     * the start page: it covers every freezer the user may access.
     */
    public function test_the_watch_list_covers_every_accessible_freezer(): void
    {
        $shared = Freezer::factory()
            ->withDrawers(1)
            ->sharedWith($this->owner)
            ->create(['name' => 'Truhe im Keller']);

        InventoryItem::factory()->expired()->create([
            'drawer_id' => $shared->drawers()->firstOrFail()->getKey(),
            'name' => 'Pommes',
        ]);

        $this->item('Brot', '2026-06-10');

        $page = $this->page();

        // The owned freezer stays the selected one - the shared one counts too.
        $this->assertSame(['Brot', 'Pommes'], $page->viewData('expired')->pluck('name')->all());

        // Every row names the freezer its item sits in.
        $page->assertSee('Pommes')->assertSee('Truhe im Keller');
    }

    /**
     * The MHD counter of the app frame - the round number on the MHD field of the
     * bottom bar - covers every freezer the user may access, not only the one that
     * is currently selected.
     */
    public function test_the_mhd_counter_covers_every_accessible_freezer(): void
    {
        $shared = Freezer::factory()
            ->withDrawers(1)
            ->sharedWith($this->owner)
            ->create(['name' => 'Truhe im Keller']);

        InventoryItem::factory()->expired()->create([
            'drawer_id' => $shared->drawers()->firstOrFail()->getKey(),
        ]);

        $this->item('Brot', '2026-06-10');
        $this->item('Erbsen', '2026-07-01');

        $this->actingAs($this->owner)
            ->get(route('expiring.index'))
            ->assertOk()
            // One warning of the owned freezer plus one of the shared one - and
            // the selected freezer has only its own item.
            ->assertSeeHtml('<span class="nav-badge">2</span>');
    }

    public function test_the_mhd_counters_stay_hidden_while_nothing_is_due(): void
    {
        $this->item('Erbsen', '2026-07-01');

        $this->actingAs($this->owner)
            ->get(route('expiring.index'))
            ->assertOk()
            ->assertDontSeeHtml('Artikel prüfen')
            ->assertDontSeeHtml('nav-badge');
    }

    public function test_the_watch_list_never_shows_items_of_a_foreign_freezer(): void
    {
        $foreign = Freezer::factory()->withDrawers(1)->create();

        InventoryItem::factory()->expired()->create([
            'drawer_id' => $foreign->drawers()->firstOrFail()->getKey(),
            'name' => 'Fremde Erbsen',
        ]);

        $this->page()
            ->assertDontSee('Fremde Erbsen')
            ->assertSee('Alles frisch');
    }

    public function test_items_without_a_best_before_date_are_never_warned_about(): void
    {
        $this->item('Beeren', null);

        $page = $this->page();

        $this->assertCount(0, $page->viewData('expired'));
        $this->assertCount(0, $page->viewData('expiringSoon'));
        $page->assertSee('Alles frisch');
    }

    public function test_an_expired_item_can_be_deleted_from_the_warning_list(): void
    {
        $item = $this->item('Brot', '2026-06-10');

        $this->page()
            ->call('deleteItem', $item->getKey())
            ->assertSet('itemFeedback', 'Artikel gelöscht.');

        $this->assertDatabaseMissing('inventory_items', ['id' => $item->getKey()]);
    }
}
