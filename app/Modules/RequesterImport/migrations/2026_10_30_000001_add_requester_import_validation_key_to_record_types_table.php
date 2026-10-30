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
        Schema::table('record_types', function (Blueprint $table) {
            $table->enum('requester_import_validation_key', ['person_name', 'birth_date'])
                ->nullable()->default(null)->after('requester_imports');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->dropColumn('requester_import_validation_key');
        });
    }
};
