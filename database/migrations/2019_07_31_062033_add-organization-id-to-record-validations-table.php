<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('record_validations', function (Blueprint $table) {
            $table->unsignedInteger('organization_id')->nullable()
                ->after('identity_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('record_validations', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });
    }
};
