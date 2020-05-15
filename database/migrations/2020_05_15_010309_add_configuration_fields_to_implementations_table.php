<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfigurationFieldsToImplementationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->string('title', 50)->after('name')->nullable();
            $table->text('description')->after('title')->nullable();
            $table->boolean('has_more_info_url')->after('description')->default(false);
            $table->string('more_info_url', 50)->after('description')->nullable();
            $table->text('description_steps')->after('more_info_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->dropColumn('description');
            $table->dropColumn('more_info_url');
            $table->dropColumn('description_steps');
        });
    }
}
