<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Link between a freezer and a user. The owner of a freezer is stored as a
 * membership with the role "owner" as well, so that authorization always has
 * exactly one source of truth.
 */
#[Fillable(['user_id', 'role'])]
class FreezerMembership extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';

    public const ROLE_MEMBER = 'member';

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }
}
