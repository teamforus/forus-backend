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
        Schema::table('prevalidation_requests', function (Blueprint $table) {
            $table->dropForeign(['prevalidation_id']);
            $table->dropColumn('prevalidation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prevalidation_requests', function (Blueprint $table) {
            $table->unsignedInteger('prevalidation_id')->nullable();

            $table->foreign('prevalidation_id')
                ->references('id')
                ->on('prevalidations')
                ->onDelete('set null');
        });
    }
};
