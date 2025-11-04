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
        Schema::table('products', function (Blueprint $table) {
            $table->string('info_duration', 400)->nullable()->after('show_on_webshop');
            $table->string('info_when', 400)->nullable()->after('info_duration');
            $table->string('info_where', 400)->nullable()->after('info_when');
            $table->string('info_more_info', 400)->nullable()->after('info_where');
            $table->string('info_attention', 400)->nullable()->after('info_more_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'info_duration',
                'info_when',
                'info_where',
                'info_more_info',
                'info_attention',
            ]);
        });
    }
};
