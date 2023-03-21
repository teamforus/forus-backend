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
            $table->renameColumn('content', 'description');
            $table->renameColumn('content_alignment', 'description_alignment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementation_pages', function (Blueprint $table) {
            $table->renameColumn('description', 'content');
            $table->renameColumn('description_alignment', 'content_alignment');
        });
    }
};
