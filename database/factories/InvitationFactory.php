<?php

namespace Database\Factories;

use App\Models\Freezer;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'freezer_id' => Freezer::factory(),
            'inviter_id' => User::factory(),
            'token_hash' => Invitation::hashToken(Str::random(64)),
            'expires_at' => Carbon::now()->addDays(Invitation::VALID_DAYS),
            'accepted_at' => null,
            'accepted_by_user_id' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => Carbon::now()->subDay(),
        ]);
    }

    /**
     * Mark the invitation as already used, so it cannot be accepted again.
     */
    public function accepted(?User $user = null): static
    {
        return $this->state(fn (): array => [
            'accepted_at' => Carbon::now(),
            'accepted_by_user_id' => $user?->getKey(),
        ]);
    }
}
