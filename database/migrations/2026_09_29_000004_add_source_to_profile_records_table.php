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
        Schema::table('profile_records', function (Blueprint $table) {
            $table->string('source')->nullable()->after('employee_id');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('profile_records', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
