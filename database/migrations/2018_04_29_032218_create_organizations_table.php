<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrganizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('identity_address', 200)->default('')->nullable();

            $table->string('name', 200)->default('');
            $table->string('iban', 200)->default('');
            $table->string('email', 200)->default('');
            $table->string('phone', 200)->default('');
            $table->string('kvk', 200)->default('');
            $table->string('btw', 200)->default('');

            $table->timestamps();

            $table->foreign('identity_address'
            )->references('address')->on('identities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organizations');
    }
}
