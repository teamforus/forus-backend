<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('event_logs')
            ->where('loggable_type', 'product')
            ->where('event', 'monitored_fields_updated')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
