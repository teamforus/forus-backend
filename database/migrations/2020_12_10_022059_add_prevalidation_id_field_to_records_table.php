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
        Schema::table('records', static function (Blueprint $table) {
            $table->unsignedInteger('prevalidation_id')->after('record_category_id')->nullable();

            $table->foreign('prevalidation_id')->references('id')->on('prevalidations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('records', static function (Blueprint $table) {
            $table->dropForeign('records_prevalidation_id_foreign');
            $table->dropColumn('prevalidation_id');
        });
    }
};
