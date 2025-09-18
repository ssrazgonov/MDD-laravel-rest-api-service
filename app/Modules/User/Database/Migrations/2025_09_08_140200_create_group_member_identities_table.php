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
        Schema::create('group_member_identities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_member_id');
            $table->string('type');
            $table->string('value');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['type', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_member_identities');
    }
};
