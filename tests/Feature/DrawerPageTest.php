<?php

namespace Tests\Feature;

use App\Livewire\Freezer\DrawerPage;
use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class DrawerPageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Drawer $drawer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $freezer = Freezer::factory()->withDrawers(2)->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Mein Gefrierschrank',
        ]);

        $this->drawer = $freezer->drawers()->firstOrFail();
    }

    /**
     * @return Testable<DrawerPage>
     */
    private function page()
    {
        return Livewire::actingAs($this->owner)->test(DrawerPage::class, ['drawer' => $this->drawer]);
    }

    /**
     * The second drawer of the same freezer - the nearest target of the move
     * tests.
     */
    private function otherDrawer(): Drawer
    {
        return $this->drawer->freezer->drawers()
            ->where('id', '!=', $this->drawer->getKey())
            ->firstOrFail();
    }

    public function test_the_drawer_shows_its_items(): void
    {
        InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
        ]);

        $this->page()->assertSee('Erbsen');
    }

    public function test_an_item_can_be_added(): void
    {
        $this->page()
            ->set('name', '  Erbsen   fein  ')
            ->set('quantity', '3')
            ->set('bestBefore', '2026-12-24')
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertSet('name', '')
            ->assertSet('quantity', '1')
            ->assertSet('bestBefore', '')
            ->assertSet('category', '')
            ->assertSet('itemFeedback', 'Artikel hinzugefügt.');

        $item = InventoryItem::query()->firstWhere('name', 'Erbsen fein');

        $this->assertNotNull($item);
        $this->assertSame($this->drawer->getKey(), $item->drawer_id);
        $this->assertSame(3, $item->quantity);
        $this->assertSame('erbsen fein', $item->name_sort);
        $this->assertSame('2026-12-24', $item->best_before?->toDateString());
        $this->assertNull($item->category);
    }

    /**
     * Sorting an item into a category is optional, but it is what the inventory
     * filter later works with. The choice stays for the next item of the same
     * drawer: whoever fills a drawer with vegetables sorts them all the same way,
     * and it stays visible in the field - nothing happens behind the back of the
     * user.
     */
    public function test_an_item_can_be_added_with_a_category(): void
    {
        $this->page()
            ->set('name', 'Erbsen')
            ->set('category', InventoryItem::CATEGORY_GEMUESE)
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertSet('category', InventoryItem::CATEGORY_GEMUESE);

        $item = InventoryItem::query()->firstWhere('name', 'Erbsen');

        $this->assertSame(InventoryItem::CATEGORY_GEMUESE, $item->category);
        $this->assertSame('Gemüse', $item->categoryLabel());
    }

    /**
     * The form is ready for the next item right away: the name and the date
     * belong to the item that was just entered, the quantity starts at one again
     * - and the category stays, because a drawer usually holds one kind of thing.
     * The cursor goes back into the name field, so a whole drawer can be filled
     * one line after the other (type, Enter, type, Enter).
     */
    public function test_the_form_is_ready_for_the_next_item(): void
    {
        $page = $this->page()
            ->set('name', 'Erbsen')
            ->set('quantity', '4')
            ->set('bestBefore', '2026-12-24')
            ->set('category', InventoryItem::CATEGORY_GEMUESE)
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertSet('name', '')
            ->assertSet('quantity', '1')
            ->assertSet('bestBefore', '')
            ->assertSet('category', InventoryItem::CATEGORY_GEMUESE);

        // The form listens for this and puts the cursor back into its name field.
        $page->assertDispatched('item-added');
        $page->assertSeeHtml('x-on:item-added.window="$refs.itemName.focus()"');

        $page->set('name', 'Spinat')->call('addItem')->assertHasNoErrors();

        $spinat = InventoryItem::query()->firstWhere('name', 'Spinat');

        $this->assertSame(InventoryItem::CATEGORY_GEMUESE, $spinat->category);
        $this->assertSame(1, $spinat->quantity);
    }

    /**
     * A list outside the drawer (the inventory, the MHD watch list) links into it
     * with "?item=<id>": the drawer appears with the form of that item already
     * open, so an item that was found by searching does not have to be searched
     * for a second time.
     */
    public function test_an_item_can_be_opened_straight_from_the_address(): void
    {
        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
            'quantity' => 3,
        ]);

        Livewire::withQueryParams(['item' => (string) $item->getKey()])
            ->actingAs($this->owner)
            ->test(DrawerPage::class, ['drawer' => $this->drawer])
            ->assertSet('editingItemId', $item->getKey())
            ->assertSet('editName', 'Erbsen')
            ->assertSet('editQuantity', '3')
            // The form of the item stands open, the "move" form does not.
            ->assertSeeHtml('wire:submit="saveItem"')
            ->assertDontSeeHtml('wire:submit="moveItem"')
            // It brings itself into view - the item may lie far down the list.
            ->assertSeeHtml('scrollIntoView');
    }

    /**
     * Only an item of this very drawer can be opened that way: an id from
     * another drawer (or an invented one) leaves the page the way it always is.
     */
    public function test_an_item_of_another_drawer_cannot_be_opened_from_the_address(): void
    {
        $foreign = InventoryItem::factory()->create(['drawer_id' => $this->otherDrawer()->getKey()]);

        foreach ([$foreign->getKey(), 999999] as $id) {
            Livewire::withQueryParams(['item' => (string) $id])
                ->actingAs($this->owner)
                ->test(DrawerPage::class, ['drawer' => $this->drawer])
                ->assertSet('editingItemId', null)
                ->assertDontSeeHtml('wire:submit="saveItem"');
        }
    }

    public function test_an_item_without_a_best_before_date_can_be_added(): void
    {
        $this->page()
            ->set('name', 'Eiswürfel')
            ->set('bestBefore', '')
            ->call('addItem')
            ->assertHasNoErrors();

        $item = InventoryItem::query()->firstWhere('name', 'Eiswürfel');

        $this->assertNull($item->best_before);
        $this->assertNull($item->category);
    }

    /**
     * Entering an item offers four quick choices for the MHD: one tap puts the
     * date that far ahead of today (see InventoryItem::BEST_BEFORE_PRESETS).
     */
    public function test_the_best_before_field_offers_the_four_quick_choices(): void
    {
        $this->page()
            ->assertSee('Schnellauswahl')
            ->assertSee('3 Monate')
            ->assertSee('6 Monate')
            ->assertSee('1 Jahr')
            ->assertSee('2 Jahre')
            ->assertSeeHtml("useBestBeforePreset('2_years')");
    }

    /**
     * The quick choice fills the field of the form, and what the field holds is
     * what the new item is saved with.
     */
    public function test_a_quick_choice_fills_the_best_before_date(): void
    {
        Carbon::setTestNow('2026-06-15 09:30:00');

        $page = $this->page()->call('useBestBeforePreset', '6_months');

        // The date is calculated; from here on the day of the run does not matter.
        Carbon::setTestNow();

        $page->assertSet('bestBefore', '2026-12-15')
            ->set('name', 'Erbsen')
            ->call('addItem')
            ->assertHasNoErrors();

        $item = InventoryItem::query()->firstWhere('name', 'Erbsen');

        $this->assertSame('2026-12-15', $item->best_before?->toDateString());
    }

    /**
     * The quick choice only fills the field - saved is what the field holds, so
     * an exact day can still be entered by hand afterwards.
     */
    public function test_the_date_of_a_quick_choice_can_still_be_corrected_by_hand(): void
    {
        $this->page()
            ->call('useBestBeforePreset', '2_years')
            ->set('bestBefore', '2026-07-01')
            ->set('name', 'Erbsen')
            ->call('addItem')
            ->assertHasNoErrors();

        $item = InventoryItem::query()->firstWhere('name', 'Erbsen');

        $this->assertSame('2026-07-01', $item->best_before?->toDateString());
    }

    /**
     * The quick choices stand in the form of an item that is being corrected as
     * well - a date is just as often corrected from a span ("another six
     * months") as it is entered the first time.
     */
    public function test_a_quick_choice_fills_the_date_of_an_item_being_edited(): void
    {
        Carbon::setTestNow('2026-06-15 09:30:00');

        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
            'best_before' => '2026-07-01',
        ]);

        $page = $this->page()
            ->call('startEditingItem', $item->getKey())
            ->assertSeeHtml("useBestBeforePreset('6_months', 'editBestBefore')")
            ->call('useBestBeforePreset', '6_months', 'editBestBefore');

        Carbon::setTestNow();

        // The field of the item changes, the one of the new item stays untouched.
        $page->assertSet('editBestBefore', '2026-12-15')
            ->assertSet('bestBefore', '')
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->assertSame('2026-12-15', $item->refresh()->best_before?->toDateString());
    }

    /**
     * The field a quick choice fills is a whitelist: a request that names any
     * other property of the component changes nothing.
     */
    public function test_a_quick_choice_cannot_fill_another_field(): void
    {
        $item = InventoryItem::factory()->withoutBestBefore()->create([
            'drawer_id' => $this->drawer->getKey(),
        ]);

        $this->page()
            ->call('startEditingItem', $item->getKey())
            ->set('editName', 'Erbsen')
            ->call('useBestBeforePreset', '2_years', 'editName')
            ->assertSet('editName', 'Erbsen')
            ->assertSet('editBestBefore', '');
    }

    /**
     * A choice the app does not know changes nothing at all - a request that
     * invents one cannot invent a date either (see
     * InventoryItem::bestBeforeForPreset()).
     */
    public function test_an_unknown_quick_choice_changes_nothing(): void
    {
        $this->page()
            ->set('bestBefore', '2026-12-24')
            ->call('useBestBeforePreset', '10_years')
            ->assertSet('bestBefore', '2026-12-24');
    }

    public function test_an_item_can_only_be_sorted_into_a_known_category(): void
    {
        $this->page()
            ->set('name', 'Erbsen')
            ->set('category', 'gibt-es-nicht')
            ->call('addItem')
            ->assertHasErrors(['category' => 'in']);

        $this->assertDatabaseCount('inventory_items', 0);
    }

    public function test_adding_an_item_validates_its_input(): void
    {
        $this->page()
            ->set('name', '')
            ->set('quantity', '0')
            ->set('bestBefore', 'kein-datum')
            ->call('addItem')
            ->assertHasErrors(['name' => 'required', 'quantity' => 'min', 'bestBefore' => 'date']);

        $this->assertDatabaseCount('inventory_items', 0);
    }

    public function test_the_quantity_can_be_increased_and_decreased(): void
    {
        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'quantity' => 2,
        ]);

        $page = $this->page();

        $page->call('incrementQuantity', $item->getKey());
        $this->assertSame(3, $item->refresh()->quantity);

        $page->call('decrementQuantity', $item->getKey());
        $page->call('decrementQuantity', $item->getKey());
        $this->assertSame(1, $item->refresh()->quantity);

        // The last piece is never taken out silently - the row asks its question
        // instead (see test_the_last_piece_asks_instead_of_doing_nothing).
        $page->call('decrementQuantity', $item->getKey());
        $this->assertSame(1, $item->refresh()->quantity);
    }

    /**
     * "−" on the last piece is the moment somebody used an item up. Doing
     * nothing at all would look like a broken button, so the row asks its
     * question - the dialog that also belongs to its trash button - and only the
     * answer deletes the item.
     */
    public function test_the_last_piece_asks_instead_of_doing_nothing(): void
    {
        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
            'quantity' => 1,
        ]);

        $this->page()
            ->call('decrementQuantity', $item->getKey())
            ->assertDispatched('confirm-dialog', name: 'item-delete-'.$item->getKey());

        // Asking is not deleting - and it is not a message either.
        $this->assertSame(1, $item->refresh()->quantity);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->getKey()]);
    }

    public function test_an_item_can_be_edited(): void
    {
        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Spinat',
            'quantity' => 2,
            'best_before' => '2026-12-24',
        ]);

        $this->page()
            ->call('startEditingItem', $item->getKey())
            ->assertSet('editingItemId', $item->getKey())
            ->assertSet('editName', 'Spinat')
            ->assertSet('editQuantity', '2')
            ->assertSet('editBestBefore', '2026-12-24')
            ->assertSet('editCategory', '')
            ->set('editName', 'Spinat fein')
            ->set('editQuantity', '5')
            ->set('editBestBefore', '')
            ->set('editCategory', InventoryItem::CATEGORY_GEMUESE)
            ->call('saveItem')
            ->assertHasNoErrors()
            ->assertSet('editingItemId', null)
            ->assertSet('itemFeedback', 'Artikel aktualisiert.');

        $item->refresh();

        $this->assertSame('Spinat fein', $item->name);
        $this->assertSame(5, $item->quantity);
        $this->assertNull($item->best_before);
        $this->assertSame(InventoryItem::CATEGORY_GEMUESE, $item->category);
    }

    /**
     * Sorting an item into a category is not a one-way street: it can be dropped
     * again while editing.
     */
    public function test_the_category_of_an_item_can_be_removed(): void
    {
        $item = InventoryItem::factory()->category(InventoryItem::CATEGORY_OBST)->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Äpfel',
        ]);

        $this->page()
            ->call('startEditingItem', $item->getKey())
            ->assertSet('editCategory', InventoryItem::CATEGORY_OBST)
            ->set('editCategory', '')
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->assertNull($item->refresh()->category);
    }

    public function test_editing_can_be_cancelled(): void
    {
        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('startEditingItem', $item->getKey())
            ->call('cancelEditingItem')
            ->assertSet('editingItemId', null)
            ->assertSet('editName', '')
            ->assertSet('editQuantity', '1')
            ->assertSet('editBestBefore', '')
            ->assertSet('editCategory', '');
    }

    public function test_an_item_can_be_deleted(): void
    {
        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('deleteItem', $item->getKey())
            ->assertSet('itemFeedback', 'Artikel gelöscht.');

        $this->assertDatabaseMissing('inventory_items', ['id' => $item->getKey()]);
    }

    /**
     * The question belongs to the dialog of the app: the trash button only opens
     * it, the delete itself hangs on the confirm button inside it.
     */
    public function test_deleting_an_item_asks_in_the_dialog_first(): void
    {
        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
        ]);

        $this->page()
            ->assertSeeHtml("name: 'item-delete-".$item->getKey()."'")
            ->assertSee('Artikel löschen?')
            ->assertSee('Möchtest du „Erbsen“ wirklich löschen?')
            ->assertSeeHtml('wire:click="deleteItem('.$item->getKey().')"')
            // The browser dialog must not come back.
            ->assertDontSeeHtml('wire:confirm');

        // Asking is not deleting.
        $this->assertDatabaseHas('inventory_items', ['id' => $item->getKey()]);
    }

    public function test_an_item_of_another_drawer_cannot_be_edited_through_this_page(): void
    {
        $otherDrawer = $this->drawer->freezer->drawers()
            ->where('id', '!=', $this->drawer->getKey())
            ->firstOrFail();

        $foreign = InventoryItem::factory()->create(['drawer_id' => $otherDrawer->getKey()]);

        // The page only ever looks inside its own drawer, so the item is unknown here.
        $this->page()->call('startEditingItem', $foreign->getKey())->assertNotFound();

        $this->assertDatabaseHas('inventory_items', ['id' => $foreign->getKey()]);
    }

    public function test_an_item_of_a_foreign_freezer_cannot_be_deleted_through_this_page(): void
    {
        $stranger = User::factory()->create();
        $foreignFreezer = Freezer::factory()->create(['owner_id' => $stranger->getKey()]);
        $foreignDrawer = Drawer::factory()->create(['freezer_id' => $foreignFreezer->getKey()]);
        $foreign = InventoryItem::factory()->create(['drawer_id' => $foreignDrawer->getKey()]);

        // The quantity and delete actions work on the item id, therefore the
        // policy - not the drawer - is what keeps foreign items safe.
        $this->page()->call('deleteItem', $foreign->getKey())->assertForbidden();
        $this->page()->call('incrementQuantity', $foreign->getKey())->assertForbidden();

        $this->assertDatabaseHas('inventory_items', ['id' => $foreign->getKey()]);
    }

    /*
     |--------------------------------------------------------------------------
     | Verschieben
     |--------------------------------------------------------------------------
     | Ein Artikel liegt selten dort, wo er hingehört: er wandert in ein anderes
     | Fach oder in einen anderen Gefrierschrank des Haushalts, ohne neu angelegt
     | zu werden. Angeboten wird nur, was der Nutzer auch erreichen darf (siehe
     | DrawerPage::moveTargets()).
     */

    public function test_the_row_offers_moving_the_item(): void
    {
        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
        ]);

        $otherDrawer = $this->otherDrawer();

        $this->page()
            ->assertSeeHtml('wire:click="startMovingItem('.$item->getKey().')"')
            ->call('startMovingItem', $item->getKey())
            ->assertSet('movingItemId', $item->getKey())
            ->assertSet('moveTargetDrawerId', '')
            ->assertSee('Artikel verschieben')
            ->assertSee('Erbsen')
            ->assertSeeHtml('wire:model="moveTargetDrawerId"')
            ->assertSee($otherDrawer->name)
            // Das eigene Fach ist kein Ziel - dort läge der Artikel ja schon.
            ->assertDontSeeHtml('<option value="'.$this->drawer->getKey().'">');
    }

    /**
     * Beim Verschieben bleibt alles andere am Artikel stehen: nur der Platz
     * ändert sich.
     */
    public function test_an_item_can_be_moved_into_another_drawer(): void
    {
        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
            'quantity' => 3,
            'best_before' => '2026-12-24',
            'category' => InventoryItem::CATEGORY_GEMUESE,
        ]);

        $otherDrawer = $this->otherDrawer();

        $this->page()
            ->call('startMovingItem', $item->getKey())
            ->set('moveTargetDrawerId', (string) $otherDrawer->getKey())
            ->call('moveItem')
            ->assertHasNoErrors()
            ->assertSet('movingItemId', null)
            ->assertSet('moveTargetDrawerId', '')
            ->assertSet('itemFeedback', '„Erbsen“ liegt jetzt in „Schublade 2“ („Mein Gefrierschrank“).');

        $item->refresh();

        $this->assertSame($otherDrawer->getKey(), $item->drawer_id);
        $this->assertSame('Erbsen', $item->name);
        $this->assertSame(3, $item->quantity);
        $this->assertSame('2026-12-24', $item->best_before?->toDateString());
        $this->assertSame(InventoryItem::CATEGORY_GEMUESE, $item->category);
    }

    /**
     * Der Weg in den anderen Gefrierschrank: dort ist der Artikel nicht
     * verschwunden, sondern liegt im richtigen Fach. Weil zwei Gefrierschränke
     * beide eine "Schublade 1" haben können, steht der Gefrierschrank als Gruppe
     * über seinen Fächern.
     */
    public function test_an_item_can_be_moved_into_another_freezer(): void
    {
        $secondFreezer = Freezer::factory()->withDrawers(1)->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Truhe im Keller',
        ]);

        $target = $secondFreezer->drawers()->firstOrFail();

        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('startMovingItem', $item->getKey())
            ->assertSeeHtml('<optgroup label="Truhe im Keller">')
            ->set('moveTargetDrawerId', (string) $target->getKey())
            ->call('moveItem')
            ->assertHasNoErrors();

        $this->assertSame($target->getKey(), $item->refresh()->drawer_id);
    }

    /**
     * Ein geteilter Gefrierschrank gehört zum Haushalt: wer Mitglied ist, darf
     * dorthin verschieben.
     */
    public function test_an_item_can_be_moved_into_a_shared_freezer(): void
    {
        $friend = User::factory()->create();

        $sharedFreezer = Freezer::factory()
            ->withDrawers(1)
            ->sharedWith($this->owner)
            ->create(['owner_id' => $friend->getKey()]);

        $target = $sharedFreezer->drawers()->firstOrFail();

        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('startMovingItem', $item->getKey())
            ->set('moveTargetDrawerId', (string) $target->getKey())
            ->call('moveItem')
            ->assertHasNoErrors();

        $this->assertSame($target->getKey(), $item->refresh()->drawer_id);
    }

    /**
     * Angeboten wird nur, was der Haushalt erreichen darf: ein fremdes Fach und
     * ein erfundenes sind keine Ziele.
     */
    public function test_an_item_can_only_be_moved_into_an_offered_drawer(): void
    {
        $stranger = User::factory()->create();
        $foreignFreezer = Freezer::factory()->withDrawers(1)->create(['owner_id' => $stranger->getKey()]);
        $foreignDrawer = $foreignFreezer->drawers()->firstOrFail();

        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        foreach ([$foreignDrawer->getKey(), 99999] as $target) {
            $this->page()
                ->call('startMovingItem', $item->getKey())
                ->set('moveTargetDrawerId', (string) $target)
                ->call('moveItem')
                ->assertHasErrors('moveTargetDrawerId');
        }

        $this->assertSame($this->drawer->getKey(), $item->refresh()->drawer_id);
    }

    public function test_an_item_cannot_be_moved_into_the_drawer_it_lies_in(): void
    {
        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('startMovingItem', $item->getKey())
            ->set('moveTargetDrawerId', (string) $this->drawer->getKey())
            ->call('moveItem')
            ->assertHasErrors('moveTargetDrawerId');

        $this->assertSame($this->drawer->getKey(), $item->refresh()->drawer_id);
    }

    public function test_moving_needs_a_choice(): void
    {
        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('startMovingItem', $item->getKey())
            ->call('moveItem')
            ->assertHasErrors(['moveTargetDrawerId' => 'required']);

        $this->assertSame($this->drawer->getKey(), $item->refresh()->drawer_id);
    }

    public function test_moving_can_be_cancelled(): void
    {
        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('startMovingItem', $item->getKey())
            ->set('moveTargetDrawerId', (string) $this->otherDrawer()->getKey())
            ->call('cancelMovingItem')
            ->assertSet('movingItemId', null)
            ->assertSet('moveTargetDrawerId', '');

        $this->assertSame($this->drawer->getKey(), $item->refresh()->drawer_id);
    }

    /**
     * Eine Zeile zeigt entweder das Bearbeiten- oder das Verschieben-Formular,
     * nie beide.
     */
    public function test_editing_and_moving_exclude_each_other(): void
    {
        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('startMovingItem', $item->getKey())
            ->call('startEditingItem', $item->getKey())
            ->assertSet('movingItemId', null)
            ->assertSet('editingItemId', $item->getKey())
            ->call('startMovingItem', $item->getKey())
            ->assertSet('editingItemId', null)
            ->assertSet('movingItemId', $item->getKey());
    }

    /**
     * Ein Haushalt mit einem einzigen Fach hat kein Ziel: das Formular sagt das,
     * statt eine Auswahl anzubieten, die es nicht gibt - abbrechen lässt es sich
     * trotzdem.
     */
    public function test_the_move_form_says_when_there_is_nothing_to_move_to(): void
    {
        $owner = User::factory()->create();
        $freezer = Freezer::factory()->withDrawers(1)->create(['owner_id' => $owner->getKey()]);
        $drawer = $freezer->drawers()->firstOrFail();

        $item = InventoryItem::factory()->create(['drawer_id' => $drawer->getKey()]);

        Livewire::actingAs($owner)->test(DrawerPage::class, ['drawer' => $drawer])
            ->call('startMovingItem', $item->getKey())
            ->assertSee('Es gibt noch kein anderes Fach und keinen anderen Gefrierschrank.')
            ->assertDontSeeHtml('wire:model="moveTargetDrawerId"')
            ->assertSeeHtml('wire:click="cancelMovingItem"');
    }

    public function test_an_item_of_another_drawer_cannot_be_moved_through_this_page(): void
    {
        $foreign = InventoryItem::factory()->create(['drawer_id' => $this->otherDrawer()->getKey()]);

        // Die Seite kennt nur den Inhalt ihres eigenen Fachs.
        $this->page()->call('startMovingItem', $foreign->getKey())->assertNotFound();
    }

    /**
     * Die Schublade zeigt weiterhin den ganzen MHD-Hinweis - Zustand, relativer
     * Zähler und Datum. Nur das Inventar kürzt die Zeile (siehe item-row).
     */
    public function test_the_drawer_keeps_the_state_and_the_relative_mhd(): void
    {
        $item = InventoryItem::factory()->expiringSoon(2)->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
        ]);

        $this->page()
            ->assertSee($item->stateLabel())
            ->assertSee($item->bestBeforeLabel())
            ->assertSee($item->formattedBestBefore());
    }

    /**
     * Die Farbe der Kategorie gehört dem Inventar: die Schublade zeigt ihre
     * Artikel weiterhin auf der weißen Karte (siehe item-row).
     */
    public function test_the_drawer_keeps_the_white_card(): void
    {
        InventoryItem::factory()->fresh()->category(InventoryItem::CATEGORY_FLEISCH)->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Hackfleisch',
        ]);

        $this->page()
            ->assertSee('Hackfleisch')
            ->assertDontSeeHtml('bg-cat-');
    }
}
