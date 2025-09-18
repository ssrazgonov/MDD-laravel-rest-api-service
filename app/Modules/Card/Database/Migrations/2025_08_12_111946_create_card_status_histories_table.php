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
        Schema::create('card_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('card_id');
            $table->string('status');
            $table->text('comment')->nullable();
            $table->uuid('changed_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_status_histories');
    }
};
