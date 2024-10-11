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
        Schema::table('email_logs', function (Blueprint $table) {
             DB::statement('CREATE INDEX id_email_logs_mailable_event_log_id ON email_logs (mailable(255))');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
             $table->dropIndex('id_email_logs_mailable_event_log_id');
        });
    }
};
