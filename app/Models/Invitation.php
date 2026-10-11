<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Invitation to a freezer. Only the SHA-256 hash of the token is stored, the
 * plain token is part of the generated link and is shown to the owner once.
 *
 * An invitation is valid for a limited time and can be used exactly once.
 */
#[Fillable(['inviter_id', 'token_hash', 'expires_at', 'accepted_at', 'accepted_by_user_id'])]
class Invitation extends Model
{
    use HasFactory;

    /** Number of days an invitation link stays valid. */
    public const VALID_DAYS = 7;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Freezer, $this>
     */
    public function freezer(): BelongsTo
    {
        return $this->belongsTo(Freezer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Create a fresh single use invitation.
     *
     * @return array{invitation: self, token: string}
     */
    public static function issue(Freezer $freezer, User $inviter, ?Carbon $expiresAt = null): array
    {
        $token = Str::random(64);

        $invitation = $freezer->invitations()->create([
            'inviter_id' => $inviter->getKey(),
            'token_hash' => self::hashToken($token),
            'expires_at' => $expiresAt ?? Carbon::now()->addDays(self::VALID_DAYS),
        ]);

        return ['invitation' => $invitation, 'token' => $token];
    }

    public static function findByToken(string $token): ?self
    {
        if (trim($token) === '') {
            return null;
        }

        return self::query()->where('token_hash', self::hashToken($token))->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * An invitation can be used while it is neither accepted nor expired.
     */
    public function isUsable(): bool
    {
        return $this->accepted_at === null && ! $this->isExpired();
    }

    /**
     * Accept the invitation for the given user: membership is created and the
     * invitation is burned. Returns false when it was already used or expired,
     * so that two people cannot accept the same link.
     */
    public function accept(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            $invitation = static::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->first();

            if ($invitation === null || ! $invitation->isUsable()) {
                return false;
            }

            $invitation->freezer->memberships()->firstOrCreate(
                ['user_id' => $user->getKey()],
                ['role' => FreezerMembership::ROLE_MEMBER],
            );

            $invitation->forceFill([
                'accepted_at' => Carbon::now(),
                'accepted_by_user_id' => $user->getKey(),
            ])->save();

            $this->setRawAttributes($invitation->getAttributes(), true);
            $this->syncOriginal();

            return true;
        });
    }
}
