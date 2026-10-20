<?php

namespace Tests\Feature;

use App\Livewire\Concerns\FollowsOfflineChanges;
use App\Livewire\ExpiringItems;
use App\Livewire\Freezer\ClosedFreezer;
use App\Livewire\Freezer\DrawerPage;
use App\Livewire\Freezer\OpenFreezer;
use App\Livewire\InventoryOverview;
use App\Models\Freezer;
use App\Models\FreezerMembership;
use App\Models\InventoryItem;
use App\Models\User;
use App\Support\OfflineInventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The offline mode: the inventory as data for the offline page and the changes
 * that page collects while there is no network.
 *
 * The page itself (public/offline.html) is a static file; these tests cover the
 * two addresses it talks to (App\Http\Controllers\OfflineController) and the
 * rules behind them (App\Support\OfflineInventory).
 */
class OfflineTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_snapshot_needs_a_session(): void
    {
        $this->getJson(route('offline.snapshot'))->assertUnauthorized();

        $this->postJson(route('offline.sync'), ['operations' => []])->assertUnauthorized();
    }

    public function test_the_snapshot_waits_for_the_confirmed_address(): void
    {
        $unverified = User::factory()->unverified()->create();

        $this->actingAs($unverified)
            ->get(route('offline.snapshot'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_the_snapshot_carries_the_inventory_of_the_user(): void
    {
        $owner = User::factory()->create();
        $drawer = $this->freezerOf($owner)->drawers()->firstOrFail();

        $item = InventoryItem::factory()->category(InventoryItem::CATEGORY_GEMUESE)->create([
            'drawer_id' => $drawer->getKey(),
            'name' => 'Erbsen',
            'quantity' => 4,
            'best_before' => '2027-01-31',
        ]);

        $response = $this->actingAs($owner)->getJson(route('offline.snapshot'))->assertOk();

        $response
            ->assertJsonPath('freezers.0.drawers.0.items.0.uuid', $item->uuid)
            ->assertJsonPath('freezers.0.drawers.0.items.0.name', 'Erbsen')
            ->assertJsonPath('freezers.0.drawers.0.items.0.quantity', 4)
            ->assertJsonPath('freezers.0.drawers.0.items.0.best_before', '2027-01-31')
            ->assertJsonPath('freezers.0.drawers.0.items.0.category', 'gemuese')
            ->assertJsonPath('warning_days', InventoryItem::WARNING_DAYS)
            ->assertJsonPath('locale', 'de');

        // The words of the interface travel along, so the offline page shows
        // what the app shows without carrying a second copy of them.
        $response
            ->assertJsonPath('labels.state.fresh', 'frisch')
            ->assertJsonPath('labels.categories.fleisch', 'Fleisch')
            ->assertJsonPath('labels.no_category', 'Ohne Kategorie')
            ->assertJsonPath('labels.mhd.in_days', 'MHD in :count Tagen')
            ->assertJsonPath('labels.presets.0.key', '3_months')
            ->assertJsonPath('labels.presets.0.months', 3);
    }

    public function test_the_snapshot_follows_the_language_of_the_account(): void
    {
        $owner = User::factory()->create(['locale' => 'it']);

        $this->freezerOf($owner);

        $this->actingAs($owner)
            ->getJson(route('offline.snapshot'))
            ->assertOk()
            ->assertJsonPath('locale', 'it')
            ->assertJsonPath('labels.state.fresh', 'fresco')
            ->assertJsonPath('labels.categories.gemuese', 'Verdure');
    }

    public function test_the_snapshot_stays_with_the_own_freezers(): void
    {
        $owner = User::factory()->create();
        $this->freezerOf($owner);

        $stranger = User::factory()->create();
        $foreign = $this->freezerOf($stranger);
        $foreign->drawers()->firstOrFail()->items()->create(['name' => 'Geheim', 'quantity' => 1]);

        $response = $this->actingAs($owner)->getJson(route('offline.snapshot'))->assertOk();

        $response->assertJsonCount(1, 'freezers');

        $this->assertStringNotContainsString('Geheim', $response->getContent());
    }

    public function test_a_member_sees_the_shared_freezer(): void
    {
        $owner = User::factory()->create();
        $freezer = $this->freezerOf($owner);

        $member = User::factory()->create();
        $freezer->memberships()->create([
            'user_id' => $member->getKey(),
            'role' => FreezerMembership::ROLE_MEMBER,
        ]);

        $this->actingAs($member)
            ->getJson(route('offline.snapshot'))
            ->assertOk()
            ->assertJsonPath('freezers.0.id', $freezer->getKey());
    }

    public function test_a_change_from_the_offline_page_is_written(): void
    {
        $owner = User::factory()->create();
        $drawer = $this->freezerOf($owner)->drawers()->firstOrFail();

        $item = InventoryItem::factory()->create([
            'drawer_id' => $drawer->getKey(),
            'name' => 'Erbsen',
            'quantity' => 2,
            'best_before' => null,
            'category' => null,
        ]);

        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [[
                'type' => 'save',
                'uuid' => $item->uuid,
                'drawer_id' => $drawer->getKey(),
                'name' => 'Erbsen (neu)',
                'quantity' => 5,
                'best_before' => '2027-05-01',
                'category' => InventoryItem::CATEGORY_GEMUESE,
            ]]])
            ->assertOk()
            ->assertJsonPath('applied', 1)
            ->assertJsonPath('skipped', 0)
            ->assertJsonPath('snapshot.freezers.0.drawers.0.items.0.name', 'Erbsen (neu)')
            ->assertJsonPath('snapshot.freezers.0.drawers.0.items.0.quantity', 5);

        $item->refresh();

        $this->assertSame('Erbsen (neu)', $item->name);
        $this->assertSame(5, $item->quantity);
        $this->assertSame('2027-05-01', $item->best_before?->toDateString());
        $this->assertSame(InventoryItem::CATEGORY_GEMUESE, $item->category);
        $this->assertSame(1, InventoryItem::query()->count());
    }

    public function test_an_item_that_was_entered_offline_is_created_once(): void
    {
        $owner = User::factory()->create();
        $drawer = $this->freezerOf($owner)->drawers()->firstOrFail();
        $uuid = (string) Str::uuid();

        $operation = [
            'type' => 'save',
            'uuid' => $uuid,
            'drawer_id' => $drawer->getKey(),
            'name' => 'Spinat',
            'quantity' => 3,
            'best_before' => null,
            'category' => null,
        ];

        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('applied', 1)
            ->assertJsonPath('skipped', 0);

        // The very same change once more: a sync whose answer never arrived has
        // to be repeatable without producing a second item.
        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('applied', 1)
            ->assertJsonPath('skipped', 0);

        $this->assertSame(1, InventoryItem::query()->count());

        $item = InventoryItem::query()->firstOrFail();

        $this->assertSame($uuid, $item->uuid);
        $this->assertSame('Spinat', $item->name);
        $this->assertSame($drawer->getKey(), $item->drawer_id);
    }

    public function test_an_item_moves_into_another_drawer(): void
    {
        $owner = User::factory()->create();
        $drawers = $this->freezerOf($owner)->drawers()->orderBy('position')->get();

        $item = InventoryItem::factory()->create([
            'drawer_id' => $drawers->first()->getKey(),
            'name' => 'Pizza',
            'quantity' => 2,
        ]);

        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [[
                'type' => 'save',
                'uuid' => $item->uuid,
                'drawer_id' => $drawers->last()->getKey(),
                'name' => 'Pizza',
                'quantity' => 2,
                'best_before' => null,
                'category' => null,
            ]]])
            ->assertOk()
            ->assertJsonPath('applied', 1);

        $this->assertSame($drawers->last()->getKey(), $item->refresh()->drawer_id);
    }

    public function test_a_best_before_date_can_be_removed(): void
    {
        $owner = User::factory()->create();
        $drawer = $this->freezerOf($owner)->drawers()->firstOrFail();

        $item = InventoryItem::factory()->fresh()->create([
            'drawer_id' => $drawer->getKey(),
            'quantity' => 1,
        ]);

        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [[
                'type' => 'save',
                'uuid' => $item->uuid,
                'drawer_id' => $drawer->getKey(),
                'name' => $item->name,
                'quantity' => 1,
                'best_before' => null,
                'category' => null,
            ]]])
            ->assertOk()
            ->assertJsonPath('applied', 1)
            ->assertJsonPath('snapshot.freezers.0.drawers.0.items.0.best_before', null);

        $this->assertNull($item->refresh()->best_before);
    }

    public function test_an_item_is_deleted(): void
    {
        $owner = User::factory()->create();
        $drawer = $this->freezerOf($owner)->drawers()->firstOrFail();
        $item = InventoryItem::factory()->create(['drawer_id' => $drawer->getKey()]);

        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [[
                'type' => 'delete',
                'uuid' => $item->uuid,
            ]]])
            ->assertOk()
            ->assertJsonPath('applied', 1)
            ->assertJsonPath('skipped', 0);

        $this->assertDatabaseCount('inventory_items', 0);
    }

    public function test_deleting_an_item_that_is_gone_already_is_no_error(): void
    {
        $owner = User::factory()->create();
        $this->freezerOf($owner);

        // The item was deleted on another phone in the meantime: the answer of
        // the repeated sync is "nothing left to do", not a failure.
        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [[
                'type' => 'delete',
                'uuid' => (string) Str::uuid(),
            ]]])
            ->assertOk()
            ->assertJsonPath('applied', 1)
            ->assertJsonPath('skipped', 0);
    }

    public function test_a_change_in_a_foreign_drawer_is_skipped(): void
    {
        $stranger = User::factory()->create();
        $foreignDrawer = $this->freezerOf($stranger)->drawers()->firstOrFail();

        $owner = User::factory()->create();
        $this->freezerOf($owner);

        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [[
                'type' => 'save',
                'uuid' => (string) Str::uuid(),
                'drawer_id' => $foreignDrawer->getKey(),
                'name' => 'Eingeschmuggelt',
                'quantity' => 1,
                'best_before' => null,
                'category' => null,
            ]]])
            ->assertOk()
            ->assertJsonPath('applied', 0)
            ->assertJsonPath('skipped', 1);

        $this->assertDatabaseMissing('inventory_items', ['name' => 'Eingeschmuggelt']);
    }

    public function test_an_item_of_another_household_stays_untouched(): void
    {
        $stranger = User::factory()->create();
        $foreignItem = InventoryItem::factory()->create([
            'drawer_id' => $this->freezerOf($stranger)->drawers()->firstOrFail()->getKey(),
            'name' => 'Geheim',
        ]);

        $owner = User::factory()->create();
        $drawer = $this->freezerOf($owner)->drawers()->firstOrFail();

        // The UUID is known - it stands in the snapshot of the other account -
        // but the item does not belong to this household.
        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [
                [
                    'type' => 'save',
                    'uuid' => $foreignItem->uuid,
                    'drawer_id' => $drawer->getKey(),
                    'name' => 'Übernommen',
                    'quantity' => 1,
                    'best_before' => null,
                    'category' => null,
                ],
                ['type' => 'delete', 'uuid' => $foreignItem->uuid],
            ]])
            ->assertOk()
            ->assertJsonPath('applied', 0)
            ->assertJsonPath('skipped', 2);

        $this->assertSame('Geheim', $foreignItem->refresh()->name);
    }

    public function test_one_impossible_operation_does_not_take_the_rest(): void
    {
        $owner = User::factory()->create();
        $drawer = $this->freezerOf($owner)->drawers()->firstOrFail();

        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [
                ['type' => 'save', 'uuid' => 'keine-uuid', 'drawer_id' => $drawer->getKey(), 'name' => 'X', 'quantity' => 1],
                ['type' => 'save', 'uuid' => (string) Str::uuid(), 'drawer_id' => $drawer->getKey(), 'name' => 'Gut', 'quantity' => 2],
                ['type' => 'verschwinden', 'uuid' => (string) Str::uuid()],
                ['type' => 'save', 'uuid' => (string) Str::uuid(), 'drawer_id' => $drawer->getKey(), 'name' => 'Zu viel', 'quantity' => 0],
                [
                    'type' => 'save', 'uuid' => (string) Str::uuid(), 'drawer_id' => $drawer->getKey(),
                    'name' => 'Erfunden', 'quantity' => 1, 'category' => 'schokolade',
                ],
            ]])
            ->assertOk()
            ->assertJsonPath('applied', 1)
            ->assertJsonPath('skipped', 4);

        $this->assertDatabaseHas('inventory_items', ['name' => 'Gut', 'quantity' => 2]);
        $this->assertDatabaseMissing('inventory_items', ['name' => 'Erfunden']);
    }

    public function test_the_quantity_never_drops_below_one(): void
    {
        $owner = User::factory()->create();
        $drawer = $this->freezerOf($owner)->drawers()->firstOrFail();

        $item = InventoryItem::factory()->create([
            'drawer_id' => $drawer->getKey(),
            'name' => 'Brot',
            'quantity' => 1,
        ]);

        $this->actingAs($owner)
            ->postJson(route('offline.sync'), ['operations' => [[
                'type' => 'save',
                'uuid' => $item->uuid,
                'drawer_id' => $drawer->getKey(),
                'name' => 'Brot',
                'quantity' => 0,
                'best_before' => null,
                'category' => null,
            ]]])
            ->assertOk()
            ->assertJsonPath('applied', 0)
            ->assertJsonPath('skipped', 1);

        $this->assertSame(1, $item->refresh()->quantity);
    }

    public function test_a_batch_that_is_too_large_is_refused(): void
    {
        $owner = User::factory()->create();
        $this->freezerOf($owner);

        $operation = ['type' => 'delete', 'uuid' => (string) Str::uuid()];

        $this->actingAs($owner)
            ->postJson(route('offline.sync'), [
                'operations' => array_fill(0, OfflineInventory::MAX_OPERATIONS + 1, $operation),
            ])
            ->assertStatus(422);
    }

    /**
     * A screen that lists items has to listen for the changes of the offline
     * page: without it a page that stood open would keep a list that is older
     * than the database (see App\Livewire\Concerns\FollowsOfflineChanges).
     */
    public function test_every_screen_with_items_follows_the_offline_changes(): void
    {
        $screens = [
            ClosedFreezer::class,
            OpenFreezer::class,
            DrawerPage::class,
            InventoryOverview::class,
            ExpiringItems::class,
        ];

        foreach ($screens as $screen) {
            $this->assertContains(
                FollowsOfflineChanges::class,
                class_uses_recursive($screen),
                $screen.' hört nicht auf die Änderungen des Offline-Modus.'
            );
        }
    }

    /**
     * A freezer of the given owner with two drawers.
     */
    private function freezerOf(User $owner): Freezer
    {
        return Freezer::factory()->withDrawers(2)->create(['owner_id' => $owner->getKey()]);
    }
}
