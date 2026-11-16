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
        Schema::table('records', function (Blueprint $table) {
            $table->index(['record_type_id', 'value']);
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropIndex(['record_type_id', 'value']);
        });
    }
};
