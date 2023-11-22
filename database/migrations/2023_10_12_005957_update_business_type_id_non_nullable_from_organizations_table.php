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
        $business_type_id = DB::table('business_types')
            ->where('key', 'association-or-organization')
            ->pluck('id')
            ->first();

        DB::table('organizations')
            ->whereNull('business_type_id')
            ->update(compact('business_type_id'));

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign('organizations_business_type_id_foreign');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedInteger('business_type_id')->nullable(false)->change();

            $table->foreign('business_type_id'
            )->references('id')->on('business_types')->onDelete('restrict');
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
