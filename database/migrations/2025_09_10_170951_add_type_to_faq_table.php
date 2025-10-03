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
        Schema::table('faq', function (Blueprint $table) {
            $table->string('type', 10)->after('faq_type')->default('question');
            $table->text('subtitle')->after('title')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faq', function (Blueprint $table) {
            $table->dropColumn('type', 'subtitle');
        });
    }
};
