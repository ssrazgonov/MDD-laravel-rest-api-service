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
        Schema::table('cards', function (Blueprint $table) {
            // Add member_id column
            $table->unsignedBigInteger('member_id')->after('id');

            // Add foreign key
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');

            // Drop old foreign key and column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            // Add back user_id
            $table->uuid('user_id')->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Drop member_id
            $table->dropForeign(['member_id']);
            $table->dropColumn('member_id');
        });
    }
};
