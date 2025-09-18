<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('card_access_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('card_id');
            $table->unsignedInteger('group_member_id');
            $table->string('access_level')->default('view');
            $table->timestamps();

            $table->unique(['card_id', 'group_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_access_controls');
    }
};
