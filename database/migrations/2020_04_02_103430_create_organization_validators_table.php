<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationValidatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('organization_validators', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('organization_id')->unsigned();
            $table->integer('validator_organization_id')->unsigned();
            $table->timestamps();

            $table->unique([
                'organization_id',
                'validator_organization_id'
            ], 'organization_validators_organization_id_validator_id_unique');

            $table->foreign('validator_organization_id'
            )->references('id')->on('organizations')->onDelete('cascade');

            $table->foreign('organization_id'
            )->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_validators');
    }
}
