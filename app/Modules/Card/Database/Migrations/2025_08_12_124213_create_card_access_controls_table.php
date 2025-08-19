<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('card_access_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('card_id');
            $table->uuid('user_id');
            $table->string('access_level')->default('view');
            $table->timestamps();

            $table->unique(['card_id', 'user_id']);

            $table->foreign('card_id')->references('id')->on('cards')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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