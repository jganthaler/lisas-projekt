<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Items that were written with muted model events (for example demo data
     * seeded through "WithoutModelEvents") kept a null sort key. Since MySQL
     * sorts nulls first and the fallback order is the primary key, the inventory
     * then showed its items in insertion order instead of alphabetically.
     *
     * The existing rows are repaired here and the column becomes mandatory, so
     * a missing sort key can no longer silently break the alphabetical order.
     */
    public function up(): void
    {
        $this->backfillSortKeys();

        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->string('name_sort')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->string('name_sort')->nullable()->change();
        });
    }

    /**
     * Fill the sort key of every item that does not have one yet.
     */
    private function backfillSortKeys(): void
    {
        $items = DB::table('inventory_items')
            ->where(fn ($query) => $query->whereNull('name_sort')->orWhere('name_sort', ''))
            ->get(['id', 'name']);

        foreach ($items as $item) {
            DB::table('inventory_items')
                ->where('id', $item->id)
                ->update(['name_sort' => $this->sortKey((string) $item->name)]);
        }
    }

    /**
     * The very same key as InventoryItem::sortKey(): lowercased and
     * transliterated, so "Äpfel" sorts like "Aepfel" on every database.
     */
    private function sortKey(string $name): string
    {
        return Str::lower(Str::ascii(Str::squish($name), 'de'));
    }
};
