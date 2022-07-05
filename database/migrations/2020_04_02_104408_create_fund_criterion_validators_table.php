<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class CreateFundCriterionValidatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('fund_criterion_validators', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fund_criterion_id')->unsigned();
            $table->integer('organization_validator_id')->unsigned();
            $table->boolean('accepted')->default(false);
            $table->timestamps();

            $table->unique([
                'fund_criterion_id',
                'organization_validator_id'
            ], 'fund_criterion_validators_criterion_id_validator_id_unique');

            $table->foreign('organization_validator_id'
            )->references('id')->on('organization_validators')->onDelete('cascade');

            $table->foreign('fund_criterion_id'
            )->references('id')->on('fund_criteria')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_criterion_validators');
    }
}
