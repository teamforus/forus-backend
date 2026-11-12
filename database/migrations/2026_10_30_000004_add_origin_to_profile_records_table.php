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
            $table->string('origin_type', 200)->nullable()->after('source');
            $table->unsignedBigInteger('origin_id')->nullable()->after('origin_type');
            $table->index(['origin_type', 'origin_id']);
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('profile_records', function (Blueprint $table) {
            $table->dropMorphs('origin');
        });
    }
};
