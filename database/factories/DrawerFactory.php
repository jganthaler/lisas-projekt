<?php

namespace Database\Factories;

use App\Models\Drawer;
use App\Models\Freezer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Drawer>
 */
class DrawerFactory extends Factory
{
    protected $model = Drawer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'freezer_id' => Freezer::factory(),
            'name' => 'Schublade '.fake()->unique()->numberBetween(1, 99),
            'position' => 0,
        ];
    }
}
