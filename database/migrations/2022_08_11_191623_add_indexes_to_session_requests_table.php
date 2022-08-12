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
        Schema::table('session_requests', function (Blueprint $table) {
            $table->index(['session_id', 'client_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('session_requests', function (Blueprint $table) {
            $table->dropIndex('session_requests_session_id_client_type_index');
        });
    }
};
