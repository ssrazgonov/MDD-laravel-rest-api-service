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
        Schema::table('member_identities', function (Blueprint $table) {
            $table->dropForeign(['member_id']);

            $table->renameColumn('member_id', 'group_member_id');

            $table->foreign('group_member_id')->references('id')->on('group_members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_identities', function (Blueprint $table) {
            $table->dropForeign(['group_member_id']);

            $table->renameColumn('group_member_id', 'member_id');

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }
};
