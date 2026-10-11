<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drawers of a freezer. "position" keeps the user defined order of the
     * drawers inside the freezer.
     */
    public function up(): void
    {
        Schema::create('drawers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freezer_id')->constrained('freezers')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['freezer_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawers');
    }
};
