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
        Schema::table('implementation_pages', function (Blueprint $table) {
            $table->enum('state', ['draft', 'public'])->default('draft')->after('page_type');
        });

        DB::table('implementation_pages')
            ->where('external', true)
            ->whereNotNull('external_url')
            ->where('external_url', '!=', '')
            ->update(['state' => 'public']);


        DB::table('implementation_pages')
            ->where('external', false)
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->update(['state' => 'public']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementation_pages', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
