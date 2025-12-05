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
        Schema::table('reservation_fields', function (Blueprint $table) {
            $table->enum('fillable_by', ['provider', 'requester'])->default('requester')->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_fields', function (Blueprint $table) {
            $table->dropColumn('fillable_by');
        });
    }
};
