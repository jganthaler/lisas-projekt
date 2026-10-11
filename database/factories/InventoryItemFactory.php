<?php

namespace Database\Factories;

use App\Models\Drawer;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'drawer_id' => Drawer::factory(),
            'name' => fake()->randomElement([
                'Erbsen', 'Spinat', 'Hackfleisch', 'Beeren', 'Pizza', 'Suppe', 'Brot', 'Äpfel',
            ]),
            'quantity' => fake()->numberBetween(1, 6),
            'best_before' => Carbon::today()->addDays(fake()->numberBetween(1, 120)),
        ];
    }

    public function expired(int $days = 3): static
    {
        return $this->state(fn (): array => [
            'best_before' => Carbon::today()->subDays($days),
        ]);
    }

    public function expiringSoon(int $days = 2): static
    {
        return $this->state(fn (): array => [
            'best_before' => Carbon::today()->addDays($days),
        ]);
    }

    public function fresh(int $days = 60): static
    {
        return $this->state(fn (): array => [
            'best_before' => Carbon::today()->addDays($days),
        ]);
    }

    public function withoutBestBefore(): static
    {
        return $this->state(fn (): array => [
            'best_before' => null,
        ]);
    }
}
