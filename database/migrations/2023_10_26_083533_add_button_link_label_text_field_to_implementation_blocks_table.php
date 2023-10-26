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
        Schema::table('implementation_blocks', function (Blueprint $table) {
            $table->string('button_link_label', 500)->after('button_link');
        });

        DB::table('implementation_blocks')->update([
            'button_link_label' => DB::raw('button_text')
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementation_blocks', function (Blueprint $table) {
            $table->dropColumn('button_link_label');
        });
    }
};
