<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membership of a user in a freezer. The owner is always stored as a member
     * with the role "owner", every invited person is a "member".
     */
    public function up(): void
    {
        Schema::create('freezer_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freezer_id')->constrained('freezers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->timestamps();

            $table->unique(['freezer_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freezer_memberships');
    }
};
