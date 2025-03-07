<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn('attachments');
            $table->dropColumn('body');
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
            $table->text('attachments')->after('mailable')->nullable();
            $table->text('body')->after('subject')->nullable();
        });
    }
};
