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
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 200);
            $table->unsignedInteger('organization_id');

            $table->enum('living_arrangement', [
                'unknown',
                'single',
                'single_parent_household',
                'cohabiting_with_partner_with_agreement',
                'cohabiting_with_partner_without_agreement',
                'cohabiting_with_income_dependent_children',
                'cohabiting_with_other_singles',
                'cohabiting_with_spouse_or_registered_partner',
                'married_or_unmarried_cohabiting',
                'other',
                'not_specified',
            ]);

            $table->unsignedInteger('count_people')->nullable();
            $table->unsignedInteger('count_minors')->nullable();
            $table->unsignedInteger('count_adults')->nullable();

            $table->string('city', 100)->nullable();
            $table->string('street', 100)->nullable();
            $table->string('house_nr', 20)->nullable();
            $table->string('house_nr_addition', 20)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('neighborhood_name', 100)->nullable();
            $table->string('municipality_name', 100)->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'uid']);

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
