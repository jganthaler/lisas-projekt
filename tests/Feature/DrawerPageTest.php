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

    /**
     * Der Weg zurück führt auf die Startseite des Gefrierschranks - nicht in
     * das Bearbeiten der Schubladen: der Pfeil verspricht mit „Zurück zum
     * Gefrierschrank“ (drawers.back_aria) die Startseite, und die Schublade
     * gehört zum Reiter „Gefrierschrank“ der Fußleiste. Das Bearbeiten der
     * Schubladen hängt am Fuß der Startseite, nicht am Rücken der Schublade.
     */
    public function test_the_way_back_leads_to_the_start_page(): void
    {
        $this->page()
            ->assertSeeHtml('href="'.route('freezer.closed').'" wire:navigate class="btn btn-ghost btn-icon"')
            ->assertDontSeeHtml('href="'.route('freezer.open').'"');
    }

    /**
     * A drawer appears with its contents, not with a form: the form for a new
     * item waits behind the plus next to the name of the drawer - the same shape
     * the app gives the freezers (see closed-freezer.blade.php), so the list
     * keeps the room on a phone.
     */
    public function test_the_drawer_appears_without_the_form_for_a_new_item(): void
    {
        $this->page()
            ->assertSet('addingItem', false)
            ->assertSeeHtml('wire:click="startAddingItem"')
            ->assertSeeHtml('aria-label="Artikel hinzufügen"')
            ->assertDontSeeHtml('wire:submit="addItem"')
            ->call('startAddingItem')
            ->assertSet('addingItem', true)
            ->assertSeeHtml('wire:submit="addItem"')
            ->assertSee('Artikel hinzufügen');
    }

    /**
     * While the form stands open, the plus is the cross that closes it again -
     * and it closes it without entering anything: the next tap on the plus
     * begins with empty fields, as if the form had never been opened. Escape
     * does the same (see the name field of the form).
     */
    public function test_the_form_for_a_new_item_can_be_closed_again(): void
    {
        $this->page()
            ->call('startAddingItem')
            ->set('name', 'Erbsen')
            ->set('quantity', '4')
            ->set('bestBefore', '2026-12-24')
            ->set('category', InventoryItem::CATEGORY_GEMUESE)
            ->assertSeeHtml('wire:click="cancelAddingItem"')
            ->assertSeeHtml('wire:keydown.escape="cancelAddingItem"')
            ->call('cancelAddingItem')
            ->assertSet('addingItem', false)
            ->assertSet('name', '')
            ->assertSet('quantity', '1')
            ->assertSet('bestBefore', '')
            ->assertSet('category', '')
            ->assertDontSeeHtml('wire:submit="addItem"');

        $this->assertSame(0, InventoryItem::query()->count());
    }

    /**
     * Solange das Formular für einen neuen Artikel offen steht, ist es die ganze
     * Schublade: der Inhalt darunter tritt weg, damit nichts von dem Feld
     * wegführt, das gerade ausgefüllt wird. Nach dem Speichern bleibt das
     * Formular offen – die Liste bleibt also weg, bis das Kreuz sie zurückholt.
     */
    public function test_the_form_for_a_new_item_is_the_whole_drawer(): void
    {
        InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen fein',
        ]);

        $this->page()
            ->assertSee('Erbsen fein')
            ->assertSee('Inhalt')
            ->assertSeeHtml('<section class="space-y-2">')
            ->call('startAddingItem')
            ->assertSeeHtml('wire:submit="addItem"')
            ->assertDontSee('Erbsen fein')
            ->assertDontSee('Inhalt')
            ->assertDontSeeHtml('<section class="space-y-2">')
            ->set('name', 'Bohnen')
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertDontSee('Erbsen fein')
            ->assertDontSee('Inhalt')
            ->call('cancelAddingItem')
            ->assertSee('Inhalt')
            ->assertSee('Erbsen fein')
            ->assertSee('Bohnen');
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
            ->call('startAddingItem')
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

        // The form stays open and listens for this, so the cursor lands back in
        // its name field.
        $page->assertSet('addingItem', true);
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
     *
     * The four stand in a grid of four equal columns over the whole width of the
     * field above them, and every pill fills its column and centres its label
     * (resources/css/app.css, .chip) - so "1 Jahr" is exactly as wide as "3
     * Monate". A row of flexible pills would make each one as wide as its word.
     *
     * The form stands open for this - it waits behind the plus next to the name
     * of the drawer (see the tests above).
     */
    public function test_the_best_before_field_offers_the_four_quick_choices(): void
    {
        $page = $this->page()
            ->call('startAddingItem')
            ->assertSee('MHD Schnellauswahl')
            ->assertSee('3 Monate')
            ->assertSee('6 Monate')
            ->assertSee('1 Jahr')
            ->assertSee('2 Jahre')
            ->assertSeeHtml("useBestBeforePreset('2_years')")
            ->assertSeeHtml('class="chip"');

        $this->assertSame(
            1,
            substr_count($page->html(), 'grid grid-cols-4 gap-1.5'),
            'Die vier Schnellauswahlen des MHD stehen nicht in vier gleich breiten Spalten.'
        );
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
     *
     * Die beiden Formulare stehen nie zusammen: solange das Formular für einen
     * neuen Artikel offen ist, tritt der Inhalt der Schublade weg, und mit ihm
     * das Formular des Artikels. Darum werden sie hier nacheinander angesehen -
     * jedes mit den vier Auswahlen in vier gleich breiten Spalten.
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
            ->call('startAddingItem')
            ->assertSeeHtml("useBestBeforePreset('6_months')");

        $this->assertSame(
            1,
            substr_count($page->html(), 'grid grid-cols-4 gap-1.5'),
            'Das Formular eines neuen Artikels bietet die Schnellauswahl nicht in vier gleich breiten Spalten.'
        );

        $page->call('cancelAddingItem')
            ->call('startEditingItem', $item->getKey())
            ->assertSeeHtml("useBestBeforePreset('6_months', 'editBestBefore')");

        $this->assertSame(
            1,
            substr_count($page->html(), 'grid grid-cols-4 gap-1.5'),
            'Das Formular eines Artikels bietet die Schnellauswahl nicht wie das Formular oben.'
        );

        $page->call('useBestBeforePreset', '6_months', 'editBestBefore');

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

    /**
     * Die beiden Knöpfe des Formulars stehen in einer festen Ordnung und in
     * derselben Größe: „Abbrechen“ links, „Speichern“ rechts, und jeder nimmt
     * die halbe Breite des Formulars ein (zwei gleich breite Spalten). Ein
     * „flex-1“ auf einem der beiden machte den einen Knopf so breit, wie es
     * seine Beschriftung zulässt – „Abbrechen“ und „Speichern“ sind aber
     * verschieden lang, die Knöpfe sollen es nicht sein. Die Ordnung im Markup
     * ist die Ordnung auf dem Bildschirm (und die Reihenfolge beim Tippen).
     */
    public function test_the_form_of_an_item_puts_cancel_left_and_save_right_in_two_equal_columns(): void
    {
        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $page = $this->page()->call('startEditingItem', $item->getKey());

        $page->assertSeeHtmlInOrder([
            'wire:click="cancelEditingItem" class="btn btn-ghost w-full"',
            'type="submit" class="btn btn-primary w-full"',
        ]);

        $this->assertSame(
            1,
            substr_count($page->html(), 'grid grid-cols-2 gap-2'),
            'Die beiden Knöpfe des Formulars stehen nicht in zwei gleich breiten Spalten.'
        );
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
     * Das Verschieben-Formular trägt dasselbe Paar wie das Bearbeiten-Formular
     * oben: „Abbrechen“ links, die Aktion rechts, beide gleich groß in zwei
     * gleich breiten Spalten (siehe den Test des Bearbeiten-Formulars).
     */
    public function test_the_move_form_puts_cancel_left_and_move_right_in_two_equal_columns(): void
    {
        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $page = $this->page()->call('startMovingItem', $item->getKey());

        // Der Knopf des Abbrechens steht im Markup über zwei Zeilen, deshalb
        // wacht hier seine Klasse: die Ordnung im Markup ist die Ordnung auf
        // dem Bildschirm.
        $page->assertSeeHtmlInOrder([
            'class="btn btn-ghost w-full"',
            'class="btn btn-primary w-full"',
        ]);

        $this->assertSame(
            1,
            substr_count($page->html(), 'grid grid-cols-2 gap-2'),
            'Die beiden Knöpfe des Verschieben-Formulars stehen nicht in zwei gleich breiten Spalten.'
        );
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
            ->assertSeeHtml('wire:click="cancelMovingItem"')
            // Ohne Ziel steht „Abbrechen“ allein und nimmt die ganze Breite.
            ->assertSeeHtml('class="btn btn-ghost w-full col-span-2"');
    }

    public function test_an_item_of_another_drawer_cannot_be_moved_through_this_page(): void
    {
        $foreign = InventoryItem::factory()->create(['drawer_id' => $this->otherDrawer()->getKey()]);

        // Die Seite kennt nur den Inhalt ihres eigenen Fachs.
        $this->page()->call('startMovingItem', $foreign->getKey())->assertNotFound();
    }

    /**
     * Die Schublade zeigt jeden Artikel in zwei Zeilen: den Namen und darunter
     * das MHD als Datum („MHD: 15.12.2026“). Zustand und relativer Zähler
     * bleiben der Warnliste - hier steht neben dem Namen nur das Datum (siehe
     * resources/views/components/drawer-item-row.blade.php).
     */
    public function test_the_drawer_shows_the_name_and_the_best_before_date(): void
    {
        $item = InventoryItem::factory()->expiringSoon(2)->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
        ]);

        $this->page()
            ->assertSee('Erbsen')
            ->assertSeeHtml('MHD: '.$item->formattedBestBefore())
            ->assertDontSee($item->stateLabel())
            ->assertDontSee($item->bestBeforeLabel());
    }

    /**
     * Ein Artikel ohne MHD lässt die Stelle des Datums nicht leer: dort steht
     * „Kein MHD“ (items.mhd.none) statt eines Etiketts ohne Datum. Die Zeile der
     * Schublade kennt zwei Zeilen, und keine davon bleibt leer (siehe
     * components/drawer-item-row.blade.php).
     */
    public function test_an_item_without_a_best_before_date_says_so(): void
    {
        InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
            'best_before' => null,
        ]);

        $this->page()
            ->assertSeeHtmlInOrder(['Erbsen', '<span>Kein MHD</span>'])
            // Kein Etikett ohne Datum und kein Zustand, der nichts sagt.
            ->assertDontSee('MHD:')
            ->assertDontSee('ohne MHD');
    }

    /**
     * Bearbeiten, Löschen und Verschieben stehen hinter dem Menü ihrer Zeile
     * („⋮“, siehe resources/views/components/menu.blade.php): die Zeile behält
     * den Namen, das MHD und die Menge, und jede der drei Aktionen bleibt die
     * Funktion dieser Seite. Jede Zeile hat ihr eigenes Menü - die Aktionen
     * nennen den Artikel, auf den sie sich beziehen.
     */
    public function test_every_item_row_carries_its_own_menu(): void
    {
        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
        ]);

        $second = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Bohnen',
        ]);

        $this->page()
            // Das Menü sitzt in der Zeile und nennt sie in seinem Namen ...
            ->assertSeeHtml('aria-haspopup="menu"')
            ->assertSeeHtml('aria-label="Weitere Aktionen für „Erbsen“"')
            ->assertSeeHtml('aria-label="Weitere Aktionen für „Bohnen“"')
            ->assertSee('Bearbeiten')
            ->assertSee('Löschen')
            ->assertSee('Verschieben')
            // ... und die Aktionen gehören zu genau diesem Artikel.
            ->assertSeeHtml('wire:click="startEditingItem('.$item->getKey().')"')
            ->assertSeeHtml('wire:click="startMovingItem('.$item->getKey().')"')
            ->assertSeeHtml("name: 'item-delete-".$item->getKey()."'")
            ->assertSeeHtml("name: 'item-delete-".$second->getKey()."'");
    }

    /**
     * Die Karte der Schublade bleibt weiß – wie jede Zeile der App: die Kategorie
     * eines Artikels dient dem Filter des Inventars (siehe drawer-item-row).
     */
    public function test_the_drawer_keeps_the_white_card(): void
    {
        InventoryItem::factory()->fresh()->category(InventoryItem::CATEGORY_FLEISCH)->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Hackfleisch',
        ]);

        $this->page()
            ->assertSee('Hackfleisch')
            ->assertSeeHtml('class="card p-3"')
            ->assertDontSeeHtml('bg-cat-');
    }

    /**
     * „&move=1“ neben „?item=<id>“ bringt statt des Bearbeiten-Formulars das
     * Verschieben-Formular hoch: die Zeile einer Liste außerhalb der Schublade
     * bietet beide Aktionen an (siehe components/item-row.blade.php).
     */
    public function test_the_move_form_of_an_item_can_be_opened_straight_from_the_address(): void
    {
        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
        ]);

        Livewire::withQueryParams(['item' => (string) $item->getKey(), 'move' => '1'])
            ->actingAs($this->owner)
            ->test(DrawerPage::class, ['drawer' => $this->drawer])
            ->assertSet('movingItemId', $item->getKey())
            ->assertSet('editingItemId', null)
            // Das Verschieben-Formular steht offen, das Bearbeiten-Formular nicht.
            ->assertSeeHtml('wire:submit="moveItem"')
            ->assertDontSeeHtml('wire:submit="saveItem"')
            // Auch dieses Formular bringt sich in Sicht - der Artikel kann weit
            // unten in der Liste liegen.
            ->assertSeeHtml('scrollIntoView');
    }
}
