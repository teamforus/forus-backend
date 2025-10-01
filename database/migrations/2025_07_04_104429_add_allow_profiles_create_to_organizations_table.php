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
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('allow_profiles_create')->after('allow_profiles');
            $table->boolean('allow_profiles_relations')->after('allow_profiles_create');
            $table->boolean('allow_profiles_households')->after('allow_profiles_relations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('allow_profiles_create');
            $table->dropColumn('allow_profiles_relations');
            $table->dropColumn('allow_profiles_households');
        });
    }
};
