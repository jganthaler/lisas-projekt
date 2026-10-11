<?php

namespace App\Models;

use App\Policies\DrawerPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single drawer (Schublade) of a freezer.
 */
#[Fillable(['name', 'position'])]
#[UsePolicy(DrawerPolicy::class)]
class Drawer extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<Freezer, $this>
     */
    public function freezer(): BelongsTo
    {
        return $this->belongsTo(Freezer::class);
    }

    /**
     * Items of this drawer, sorted alphabetically (locale safe).
     *
     * @return HasMany<InventoryItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class)->orderBy('name_sort')->orderBy('id');
    }

    public function itemCount(): int
    {
        return $this->items()->count();
    }

    public function expiringItemCount(): int
    {
        return $this->items()->expiringSoon()->count();
    }

    public function expiredItemCount(): int
    {
        return $this->items()->expired()->count();
    }

    public function warningItemCount(): int
    {
        return $this->expiredItemCount() + $this->expiringItemCount();
    }
}
