<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class DropBunqIdealIssuersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::drop('bunq_ideal_issuers');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::create('bunq_ideal_issuers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 200);
            $table->string('bic', 200);
            $table->boolean('sandbox');
            $table->timestamps();
        });
    }
}
