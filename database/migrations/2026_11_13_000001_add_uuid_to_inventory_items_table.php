<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Every item carries a UUID.
     *
     * The offline page (public/offline.html) lets an item be entered without a
     * network: the browser invents the UUID of that item, and the sync
     * (App\Support\OfflineInventory) creates it under exactly that UUID - so a
     * second sync of the same change cannot produce a second copy of the item.
     * The rows that were written before this column existed get theirs here.
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique();
        });

        $this->backfillUuids();

        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }

    /**
     * Give every item that does not have a UUID yet one of its own.
     */
    private function backfillUuids(): void
    {
        DB::table('inventory_items')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(200, function ($items): void {
                foreach ($items as $item) {
                    DB::table('inventory_items')
                        ->where('id', $item->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });
    }
};
