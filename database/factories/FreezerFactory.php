<?php

namespace Database\Factories;

use App\Models\Freezer;
use App\Models\FreezerMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Freezer>
 */
class FreezerFactory extends Factory
{
    protected $model = Freezer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Gefrierschrank '.fake()->unique()->numberBetween(1, 999),
            'owner_id' => User::factory(),
        ];
    }

    /**
     * A freezer without an owner membership would be inaccessible for everybody,
     * so the membership of the owner is always created as well.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Freezer $freezer): void {
            $freezer->memberships()->firstOrCreate(
                ['user_id' => $freezer->owner_id],
                ['role' => FreezerMembership::ROLE_OWNER],
            );
        });
    }

    public function withDrawers(int $count = Freezer::DEFAULT_DRAWER_COUNT): static
    {
        return $this->afterCreating(function (Freezer $freezer) use ($count): void {
            for ($position = 1; $position <= $count; $position++) {
                $freezer->drawers()->create([
                    'name' => "Schublade {$position}",
                    'position' => $position,
                ]);
            }
        });
    }

    /**
     * Attach an existing user as a regular member.
     */
    public function sharedWith(User $user): static
    {
        return $this->afterCreating(function (Freezer $freezer) use ($user): void {
            $freezer->memberships()->firstOrCreate(
                ['user_id' => $user->getKey()],
                ['role' => FreezerMembership::ROLE_MEMBER],
            );
        });
    }
}
