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
 *
 * The UUID is the identity the offline page works with: it can only invent an
 * item itself (no database, no id), so it sends the UUID along and the sync
 * creates the row under exactly that one (see App\Support\OfflineInventory).
 * A new item gets its UUID here, an item that already carries one keeps it.
 */
#[Fillable(['name', 'quantity', 'best_before', 'category'])]
#[UsePolicy(InventoryItemPolicy::class)]
class InventoryItem extends Model
{
    use HasFactory;

    /** Items that expire within this many days trigger the MHD warning. */
    public const WARNING_DAYS = 7;

    /*
     | The quick choices of the item form: one tap puts the best before date
     | that far ahead ("3 Monate", "6 Monate", "1 Jahr", "2 Jahre"). The key is
     | what the form sends (see DrawerPage::useBestBeforePreset()), the value is
     | the number of months added to today - a year is twelve of them, two years
     | are twenty-four, so there is only one calculation for all four.
     */
    public const BEST_BEFORE_PRESETS = [
        '3_months' => 3,
        '6_months' => 6,
        '1_year' => 12,
        '2_years' => 24,
    ];

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

    public const CATEGORY_SOSSE = 'sosse';

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
        static::creating(function (InventoryItem $item): void {
            // An item that comes from the offline page already carries the UUID
            // of the browser; only a row without one gets a fresh one here.
            $item->uuid ??= (string) Str::uuid();
        });

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
            self::CATEGORY_SOSSE => __('items.categories.sosse'),
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
     * Background of the row in the inventory, one tint per category.
     *
     * The names are the utilities of the palette (the "--color-cat-*" tokens in
     * resources/css/app.css); the dark scheme swaps the values behind those
     * tokens, so the same class serves both schemes and the view needs no
     * variant of its own. "Sonstiges" and an item without a category stay null:
     * the plain card of the list is white, which is exactly what they should
     * look like.
     */
    public function categoryTintClass(): ?string
    {
        return match ($this->category) {
            self::CATEGORY_FLEISCH => 'bg-cat-fleisch',
            self::CATEGORY_FISCH => 'bg-cat-fisch',
            self::CATEGORY_GEMUESE => 'bg-cat-gemuese',
            self::CATEGORY_SUPPE => 'bg-cat-suppe',
            self::CATEGORY_OBST => 'bg-cat-obst',
            self::CATEGORY_SOSSE => 'bg-cat-sosse',
            default => null,
        };
    }

    /**
     * The date a quick choice points at: today plus the months of that choice.
     *
     * Deliberately without overflow, because the date has to stay inside the
     * month it names - "3 Monate" from the 31.08. is the 30.11., not the
     * 01.12., which would already be a different month. An unknown key yields
     * null, so a request that invents a choice cannot invent a date either.
     */
    public static function bestBeforeForPreset(string $preset): ?string
    {
        if (! array_key_exists($preset, self::BEST_BEFORE_PRESETS)) {
            return null;
        }

        return Carbon::today()
            ->addMonthsNoOverflow(self::BEST_BEFORE_PRESETS[$preset])
            ->toDateString();
    }

    /**
     * Every quick choice as key => label, in the order the interface offers
     * them.
     *
     * The labels come from the translation files (lang/<language>/drawers.php,
     * because the form stands on the drawer page), so every language sees its
     * own words - the keys are the ones the calculation above works with.
     *
     * @return array<string, string>
     */
    public static function bestBeforePresetLabels(): array
    {
        $labels = [];

        foreach (array_keys(self::BEST_BEFORE_PRESETS) as $preset) {
            $labels[$preset] = __('drawers.quick_best_before.'.$preset);
        }

        return $labels;
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
