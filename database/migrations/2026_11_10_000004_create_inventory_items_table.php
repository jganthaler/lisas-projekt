<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A single food item inside a drawer. The same product name may exist
     * multiple times (different batches / different best before dates), so no
     * unique constraint on the name is used.
     *
     * "name_sort" stores a lowercased, transliterated version of the name so
     * that alphabetical sorting (including German umlauts) behaves identically
     * on MySQL and SQLite.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drawer_id')->constrained('drawers')->cascadeOnDelete();
            $table->string('name');
            $table->string('name_sort')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->date('best_before')->nullable();
            $table->timestamps();

            $table->index(['drawer_id', 'name_sort']);
            $table->index('best_before');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
