<?php

namespace App\Models;

use App\Policies\InventoryItemPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A single food item inside a drawer.
 *
 * The same product may exist several times (different batches / different best
 * before dates), therefore the name is not unique. All sorting happens on the
 * pre-calculated "name_sort" column, which keeps the alphabetical order
 * identical for German umlauts on MySQL and SQLite. The column is mandatory and
 * is filled by the "saving" hook below, so an item can never end up without it.
 */
#[Fillable(['name', 'quantity', 'best_before', 'category'])]
#[UsePolicy(InventoryItemPolicy::class)]
class InventoryItem extends Model
{
    use HasFactory;

    /** Items that expire within this many days trigger the MHD warning. */
    public const WARNING_DAYS = 7;

    /*
     | The categories an item may be assigned to. The slug is what is stored and
     | filtered with, the label is what the interface shows - so an umlaut never
     | has to travel through the database. The labels come from the translation
     | files (lang/<language>/items.php), so every language sees its own words.
     */
    public const CATEGORY_FLEISCH = 'fleisch';

    public const CATEGORY_FISCH = 'fisch';

    public const CATEGORY_GEMUESE = 'gemuese';

    public const CATEGORY_SUPPE = 'suppe';

    public const CATEGORY_OBST = 'obst';

    public const CATEGORY_SONSTIGES = 'sonstiges';

    public const STATE_EXPIRED = 'expired';

    public const STATE_SOON = 'soon';

    public const STATE_FRESH = 'fresh';

    public const STATE_UNKNOWN = 'unknown';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'best_before' => 'date',
            'quantity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (InventoryItem $item): void {
            $item->name = self::normalizeName((string) $item->name);
            $item->name_sort = self::sortKey($item->name);
        });
    }

    /**
     * Trim the name and collapse repeated whitespace.
     */
    public static function normalizeName(string $name): string
    {
        return Str::squish($name);
    }

    /**
     * Lowercased, transliterated sort key ("Äpfel" becomes "aepfel").
     */
    public static function sortKey(string $name): string
    {
        return Str::lower(Str::ascii(self::normalizeName($name), 'de'));
    }

    /**
     * Every category as slug => label, in the order the interface offers them.
     *
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_FLEISCH => __('items.categories.fleisch'),
            self::CATEGORY_FISCH => __('items.categories.fisch'),
            self::CATEGORY_GEMUESE => __('items.categories.gemuese'),
            self::CATEGORY_SUPPE => __('items.categories.suppe'),
            self::CATEGORY_OBST => __('items.categories.obst'),
            self::CATEGORY_SONSTIGES => __('items.categories.sonstiges'),
        ];
    }

    /**
     * The slugs of every category - what a form may send and a filter accepts.
     *
     * @return list<string>
     */
    public static function categoryKeys(): array
    {
        return array_keys(self::categoryLabels());
    }

    /**
     * Label of the assigned category, or null while the item has none.
     */
    public function categoryLabel(): ?string
    {
        return self::categoryLabels()[$this->category] ?? null;
    }

    /**
     * @return BelongsTo<Drawer, $this>
     */
    public function drawer(): BelongsTo
    {
        return $this->belongsTo(Drawer::class);
    }

    public function freezer(): ?Freezer
    {
        return $this->drawer?->freezer;
    }

    /**
     * Items with a best before date today or within the warning window.
     */
    #[Scope]
    public function expiringSoon(Builder $query): void
    {
        $today = Carbon::today();

        $query->whereNotNull('best_before')
            ->whereDate('best_before', '>=', $today->toDateString())
            ->whereDate('best_before', '<=', $today->copy()->addDays(self::WARNING_DAYS)->toDateString());
    }

    /**
     * Items whose best before date already passed.
     */
    #[Scope]
    public function expired(Builder $query): void
    {
        $query->whereNotNull('best_before')
            ->whereDate('best_before', '<', Carbon::today()->toDateString());
    }

    /**
     * Items that carry a best before date at all.
     */
    #[Scope]
    public function withBestBefore(Builder $query): void
    {
        $query->whereNotNull('best_before');
    }

    #[Scope]
    public function alphabetical(Builder $query): void
    {
        $query->orderBy('name_sort')->orderBy('id');
    }

    /**
     * Only the items that were sorted into one category.
     */
    #[Scope]
    public function withCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    /**
     * Days until the best before date: negative when already expired,
     * null when no date was entered.
     */
    public function daysUntilBestBefore(): ?int
    {
        if ($this->best_before === null) {
            return null;
        }

        $today = Carbon::today();
        $target = $this->best_before->copy()->startOfDay();

        return (int) round(($target->getTimestamp() - $today->getTimestamp()) / 86400);
    }

    public function state(): string
    {
        $days = $this->daysUntilBestBefore();

        return match (true) {
            $days === null => self::STATE_UNKNOWN,
            $days < 0 => self::STATE_EXPIRED,
            $days <= self::WARNING_DAYS => self::STATE_SOON,
            default => self::STATE_FRESH,
        };
    }

    public function isExpired(): bool
    {
        return $this->state() === self::STATE_EXPIRED;
    }

    public function isExpiringSoon(): bool
    {
        return $this->state() === self::STATE_SOON;
    }

    public function formattedBestBefore(): ?string
    {
        return $this->best_before?->format('d.m.Y');
    }

    /**
     * Short relative label of the best before date ("heute", "in 3 Tagen", ...).
     */
    public function bestBeforeLabel(): string
    {
        $days = $this->daysUntilBestBefore();

        return match (true) {
            $days === null => __('items.mhd.none'),
            $days < -1 => __('items.mhd.days_ago', ['count' => abs($days)]),
            $days === -1 => __('items.mhd.yesterday'),
            $days === 0 => __('items.mhd.today'),
            $days === 1 => __('items.mhd.tomorrow'),
            default => __('items.mhd.in_days', ['count' => $days]),
        };
    }

    /**
     * Badge class matching the state, so every list shows the same colours.
     */
    public function stateBadgeClass(): string
    {
        return match ($this->state()) {
            self::STATE_EXPIRED => 'badge-danger',
            self::STATE_SOON => 'badge-warn',
            self::STATE_FRESH => 'badge-ok',
            default => 'badge-neutral',
        };
    }

    public function stateLabel(): string
    {
        return match ($this->state()) {
            self::STATE_EXPIRED => __('items.state.expired'),
            self::STATE_SOON => __('items.state.soon'),
            self::STATE_FRESH => __('items.state.fresh'),
            default => __('items.state.unknown'),
        };
    }
}
