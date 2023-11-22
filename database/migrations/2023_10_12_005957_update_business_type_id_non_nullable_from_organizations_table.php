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
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign('organizations_business_type_id_foreign');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedInteger('business_type_id')->nullable(false)->change();

            $table->foreign('business_type_id'
            )->references('id')->on('business_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign('organizations_business_type_id_foreign');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedInteger('business_type_id')->nullable()->change();

            $table->foreign('business_type_id'
            )->references('id')->on('business_types')->onDelete('set null');
        });
    }
};
