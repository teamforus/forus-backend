<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('allow_requester_imports')->default(false)->after('allow_profiles_create');
            $table->string('requester_import_mapping_key', 200)->default('default')->after('allow_requester_imports');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['requester_import_mapping_key', 'allow_requester_imports']);
        });
    }
};
