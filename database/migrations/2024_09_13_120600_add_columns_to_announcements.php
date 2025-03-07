<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dateTime('start_at')->nullable()->after('expire_at');
            $table->unsignedInteger('role_id')->nullable()->after('implementation_id');
            $table->unsignedInteger('organization_id')->nullable()->after('role_id');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['organization_id']);
            $table->dropColumn('start_at', 'role_id', 'organization_id');
        });
    }
};
