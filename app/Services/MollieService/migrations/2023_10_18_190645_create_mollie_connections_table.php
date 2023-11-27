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
        Schema::create('mollie_connections', function (Blueprint $table) {
            $table->id();
            $table->string('mollie_organization_id')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable();
            $table->string('postcode', 20)->nullable();
            $table->string('state_code')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('business_type')->nullable();
            $table->string('registration_number')->nullable();
            $table->enum('connection_state', ['active', 'pending'])->default('pending');
            $table->string('onboarding_state', 20)->default('needs-data');
            $table->unsignedInteger('organization_id');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('mollie_connections');
    }
};
