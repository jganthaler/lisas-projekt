<?php

namespace Tests\Feature;

use App\Livewire\Freezer\DrawerPage;
use App\Livewire\InventoryOverview;
use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\FreezerMembership;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Authorization is always resolved through the freezer memberships: neither a
 * stranger nor a guest may read or change content of a foreign freezer.
 */
class FreezerAccessTest extends TestCase
{
    use RefreshDatabase;

    private function drawerOf(User $owner): Drawer
    {
        $freezer = Freezer::factory()->withDrawers(1)->create(['owner_id' => $owner->getKey()]);

        return $freezer->drawers()->firstOrFail();
    }

    public function test_a_stranger_cannot_open_a_foreign_drawer(): void
    {
        $drawer = $this->drawerOf(User::factory()->create());
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('drawers.show', $drawer))
            ->assertForbidden();
    }

    public function test_a_member_may_open_a_shared_drawer(): void
    {
        $owner = User::factory()->create();
        $freezer = Freezer::factory()->withDrawers(1)->create(['owner_id' => $owner->getKey()]);
        $drawer = $freezer->drawers()->firstOrFail();

        $member = User::factory()->create();
        $freezer->memberships()->create([
            'user_id' => $member->getKey(),
            'role' => FreezerMembership::ROLE_MEMBER,
        ]);

        $this->actingAs($member)
            ->get(route('drawers.show', $drawer))
            ->assertOk();
    }

    public function test_a_stranger_cannot_reach_the_drawer_component(): void
    {
        $drawer = $this->drawerOf(User::factory()->create());

        Livewire::actingAs(User::factory()->create())
            ->test(DrawerPage::class, ['drawer' => $drawer])
            ->assertForbidden();
    }

    public function test_items_of_a_foreign_freezer_cannot_be_changed(): void
    {
        $drawer = $this->drawerOf(User::factory()->create());
        $item = InventoryItem::factory()->create([
            'drawer_id' => $drawer->getKey(),
            'quantity' => 4,
        ]);

        $stranger = User::factory()->create();

        Livewire::actingAs($stranger)
            ->test(InventoryOverview::class)
            ->call('deleteItem', $item->getKey())
            ->assertForbidden();

        Livewire::actingAs($stranger)
            ->test(InventoryOverview::class)
            ->call('incrementQuantity', $item->getKey())
            ->assertForbidden();

        $this->assertSame(4, $item->refresh()->quantity);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->getKey()]);
    }

    public function test_the_inventory_shows_all_accessible_freezers_but_no_foreign_ones(): void
    {
        $owner = User::factory()->create();

        $own = Freezer::factory()->withDrawers(1)->create(['owner_id' => $owner->getKey()]);
        $ownItem = InventoryItem::factory()->create([
            'drawer_id' => $own->drawers()->firstOrFail()->getKey(),
            'name' => 'Eigene Erbsen',
        ]);

        // Shared with the owner, so these items belong on the list as well.
        $shared = Freezer::factory()->withDrawers(1)->sharedWith($owner)->create();
        $sharedItem = InventoryItem::factory()->create([
            'drawer_id' => $shared->drawers()->firstOrFail()->getKey(),
            'name' => 'Geteilte Erbsen',
        ]);

        $foreignFreezer = Freezer::factory()->withDrawers(1)->create();
        $foreignItem = InventoryItem::factory()->create([
            'drawer_id' => $foreignFreezer->drawers()->firstOrFail()->getKey(),
            'name' => 'Fremde Erbsen',
        ]);

        Livewire::actingAs($owner)
            ->test(InventoryOverview::class)
            ->assertViewHas('items', fn ($items) => $items->pluck('id')->all() === [$ownItem->getKey(), $sharedItem->getKey()])
            ->assertDontSee('Fremde Erbsen');

        $this->assertNotSame($ownItem->getKey(), $foreignItem->getKey());
    }
}
