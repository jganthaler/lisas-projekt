<?php

namespace Database\Seeders;

use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\InventoryItem;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a small, realistic demo: two users,
     * a freezer filled with items in every MHD state and one open invitation.
     *
     * Model events are deliberately not muted here: every item derives its
     * alphabetical sort key ("name_sort") in a "saving" hook, and a missing key
     * would silently leave the inventory in insertion order.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Lisa Muster',
            'email' => 'lisa@example.com',
        ]);

        $partner = User::factory()->create([
            'name' => 'Jonas Muster',
            'email' => 'jonas@example.com',
        ]);

        $freezer = Freezer::provisionFor($owner);
        $freezer->update(['name' => 'Gefrierschrank Küche']);

        $this->fillFreezer($freezer, [
            'Schublade 1' => [
                ['Erbsen', 3, 120, InventoryItem::CATEGORY_GEMUESE],
                ['Spinat', 2, 4, InventoryItem::CATEGORY_GEMUESE],
                ['Pizza', 1, -2, InventoryItem::CATEGORY_SONSTIGES],
            ],
            'Schublade 2' => [
                ['Hackfleisch', 2, 30, InventoryItem::CATEGORY_FLEISCH],
                ['Lachs', 2, 60, InventoryItem::CATEGORY_FISCH],
                ['Suppe', 4, 0, InventoryItem::CATEGORY_SUPPE],
                ['Tomatensoße', 2, 90, InventoryItem::CATEGORY_SOSSE],
                ['Beeren', 1, null, InventoryItem::CATEGORY_OBST],
            ],
            'Schublade 3' => [
                ['Äpfel', 6, 14, InventoryItem::CATEGORY_OBST],
                ['Brot', 1, -10, InventoryItem::CATEGORY_SONSTIGES],
            ],
        ]);

        // Second freezer of the household: owned by the partner, shared with Lisa.
        $second = Freezer::factory()
            ->withDrawers(2)
            ->sharedWith($owner)
            ->create([
                'name' => 'Truhe im Keller',
                'owner_id' => $partner->getKey(),
            ]);

        $this->fillFreezer($second, [
            'Schublade 1' => [
                ['Pommes', 2, 200, InventoryItem::CATEGORY_GEMUESE],
                ['Gemüsemix', 5, 6, InventoryItem::CATEGORY_GEMUESE],
            ],
            'Schublade 2' => [
                ['Eiscreme', 3, 45, InventoryItem::CATEGORY_SONSTIGES],
            ],
        ]);

        // One open invitation so the sharing page has something to show.
        Invitation::issue($freezer, $owner);
    }

    /**
     * Create the given items inside the matching drawers of a freezer. Every row
     * is [name, quantity, days until the MHD, category].
     *
     * @param  array<string, array<int, array{0: string, 1: int, 2: int|null, 3: string|null}>>  $contents
     */
    protected function fillFreezer(Freezer $freezer, array $contents): void
    {
        foreach ($contents as $drawerName => $items) {
            $drawer = $freezer->drawers()->where('name', $drawerName)->first()
                ?? Drawer::factory()->create([
                    'freezer_id' => $freezer->getKey(),
                    'name' => $drawerName,
                ]);

            foreach ($items as [$name, $quantity, $daysUntilBestBefore, $category]) {
                InventoryItem::factory()->create([
                    'drawer_id' => $drawer->getKey(),
                    'name' => $name,
                    'quantity' => $quantity,
                    'best_before' => $daysUntilBestBefore === null
                        ? null
                        : Carbon::today()->addDays($daysUntilBestBefore),
                    'category' => $category,
                ]);
            }
        }
    }
}
