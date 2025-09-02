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
        Schema::table('reservation_extra_payments', function (Blueprint $table) {
            $table->string('cancellation_note', 255)->nullable()->after('currency');
            $table->boolean('cancellation_note_share')->default(false)->after('cancellation_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_extra_payments', function (Blueprint $table) {
            $table->dropColumn('cancellation_note', 'cancellation_note_share');
        });
    }
};
