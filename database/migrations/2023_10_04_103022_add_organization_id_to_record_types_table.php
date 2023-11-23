<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->unsignedInteger('organization_id')->nullable()->after('type');

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->dropForeign('record_types_organization_id_foreign');
            $table->dropColumn('organization_id');
        });
    }
};
