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
    public function up()
    {
        Schema::create('implementation_page_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('implementation_id')->nullable();
            $table->string('page_key', 50)->nullable();
            $table->string('page_config_key', 50);
            $table->boolean('is_active')->default(true);

//            $table->foreign('page_key'
//            )->references('page_type')->on('implementation_pages')->onDelete('cascade');

            $table->foreign('implementation_id'
            )->references('id')->on('implementations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('implementation_page_configs');
    }
};