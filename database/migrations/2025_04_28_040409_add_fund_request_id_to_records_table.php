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
        Schema::table('records', function (Blueprint $table) {
            $table->unsignedInteger('fund_request_id')->nullable()->after('prevalidation_id');
            $table->unsignedInteger('organization_id')->nullable()->after('fund_request_id');

            $table->foreign('fund_request_id')
                ->references('id')
                ->on('fund_requests')
                ->onDelete('restrict');

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('restrict');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropForeign(['fund_request_id']);
            $table->dropForeign(['organization_id']);

            $table->dropColumn('fund_request_id');
            $table->dropColumn('organization_id');
        });
    }
};
