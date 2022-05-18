<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddContentAlignmentToImplementationPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('implementation_pages', function (Blueprint $table) {
            $table->enum('content_alignment', [
                'left', 'center', 'right'
            ])->default('left')->after('content');
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
            $table->dropColumn('content_alignment');
        });
    }
}
