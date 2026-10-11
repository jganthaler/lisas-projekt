<?php

namespace Tests\Feature;

use App\Livewire\Freezer\DrawerPage;
use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $freezer = Freezer::factory()->withDrawers(2)->create(['owner_id' => $this->owner->getKey()]);

        $this->drawer = $freezer->drawers()->firstOrFail();
    }

    /**
     * @return Testable<DrawerPage>
     */
    private function page()
    {
        return Livewire::actingAs($this->owner)->test(DrawerPage::class, ['drawer' => $this->drawer]);
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
            ->assertSet('itemFeedback', 'Artikel hinzugefügt.');

        $item = InventoryItem::query()->firstWhere('name', 'Erbsen fein');

        $this->assertNotNull($item);
        $this->assertSame($this->drawer->getKey(), $item->drawer_id);
        $this->assertSame(3, $item->quantity);
        $this->assertSame('erbsen fein', $item->name_sort);
        $this->assertSame('2026-12-24', $item->best_before?->toDateString());
    }

    public function test_an_item_without_a_best_before_date_can_be_added(): void
    {
        $this->page()
            ->set('name', 'Eiswürfel')
            ->set('bestBefore', '')
            ->call('addItem')
            ->assertHasNoErrors();

        $this->assertNull(InventoryItem::query()->firstWhere('name', 'Eiswürfel')?->best_before);
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

        // The last piece is only removed by an explicit delete.
        $page->call('decrementQuantity', $item->getKey());
        $this->assertSame(1, $item->refresh()->quantity);
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
            ->set('editName', 'Spinat fein')
            ->set('editQuantity', '5')
            ->set('editBestBefore', '')
            ->call('saveItem')
            ->assertHasNoErrors()
            ->assertSet('editingItemId', null)
            ->assertSet('itemFeedback', 'Artikel aktualisiert.');

        $item->refresh();

        $this->assertSame('Spinat fein', $item->name);
        $this->assertSame(5, $item->quantity);
        $this->assertNull($item->best_before);
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
            ->assertSet('editBestBefore', '');
    }

    public function test_an_item_can_be_deleted(): void
    {
        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('deleteItem', $item->getKey())
            ->assertSet('itemFeedback', 'Artikel gelöscht.');

        $this->assertDatabaseMissing('inventory_items', ['id' => $item->getKey()]);
    }

    public function test_deleting_an_item_asks_inside_the_row_first(): void
    {
        $item = InventoryItem::factory()->create([
            'drawer_id' => $this->drawer->getKey(),
            'name' => 'Erbsen',
        ]);

        $page = $this->page()
            ->call('confirmDelete', $item->getKey())
            ->assertSet('confirmingItemId', $item->getKey())
            ->assertSee('Möchtest du „Erbsen“ wirklich löschen?')
            ->assertSeeHtml('wire:click="deleteItem('.$item->getKey().')"')
            // The browser dialog must not come back.
            ->assertDontSeeHtml('wire:confirm');

        // Asking is not deleting.
        $this->assertDatabaseHas('inventory_items', ['id' => $item->getKey()]);

        $page->call('cancelDelete')
            ->assertSet('confirmingItemId', null)
            ->assertDontSee('Möchtest du „Erbsen“ wirklich löschen?');

        $this->assertDatabaseHas('inventory_items', ['id' => $item->getKey()]);
    }

    public function test_the_delete_question_can_be_answered_with_yes(): void
    {
        $item = InventoryItem::factory()->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('confirmDelete', $item->getKey())
            ->call('deleteItem', $item->getKey())
            ->assertSet('confirmingItemId', null)
            ->assertSet('itemFeedback', 'Artikel gelöscht.');

        $this->assertDatabaseMissing('inventory_items', ['id' => $item->getKey()]);
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
        $this->page()->call('confirmDelete', $foreign->getKey())->assertForbidden();
        $this->page()->call('incrementQuantity', $foreign->getKey())->assertForbidden();

        $this->assertDatabaseHas('inventory_items', ['id' => $foreign->getKey()]);
    }
}
