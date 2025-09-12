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
        Schema::create('member_identities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('identity_type'); // 'keycloak', 'snils', 'passport', etc.
            $table->string('identity_value');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['identity_type', 'identity_value']);
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->index(['member_id', 'identity_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_identities');
    }
};
