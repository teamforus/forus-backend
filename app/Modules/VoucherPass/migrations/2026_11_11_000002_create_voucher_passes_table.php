<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('voucher_passes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id');
            $table->string('external_id', 200)->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
            $table->unsignedInteger('owner_identity_id');
            $table->unsignedInteger('target_identity_id');
            $table->string('code', 20)->unique('voucher_passes_code_unique');
            $table->string('print_label', 200);
            $table->enum('state', ['active', 'deactivated']);
            $table->boolean('print_requested')->default(false);
            $table->unsignedInteger('employee_id')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'external_id'], 'voucher_passes_organization_external_id_unique');

            $table->foreign('organization_id', 'voucher_passes_organization_fk')
                ->references('id')->on('organizations')->restrictOnDelete();

            $table->foreign('owner_identity_id', 'voucher_passes_owner_identity_fk')
                ->references('id')->on('identities')->restrictOnDelete();

            $table->foreign('target_identity_id', 'voucher_passes_target_identity_fk')
                ->references('id')->on('identities')->restrictOnDelete();

            $table->foreign('employee_id', 'voucher_passes_employee_fk')
                ->references('id')->on('employees')->restrictOnDelete();
        });

        Schema::create('voucher_pass_type_links', function (Blueprint $table) {
            $table->unsignedBigInteger('voucher_pass_id');
            $table->unsignedBigInteger('voucher_pass_type_id');

            $table->primary(['voucher_pass_id', 'voucher_pass_type_id'], 'voucher_pass_type_links_primary');

            $table->foreign('voucher_pass_id', 'voucher_pass_type_links_pass_fk')
                ->references('id')->on('voucher_passes')->restrictOnDelete();

            $table->foreign('voucher_pass_type_id', 'voucher_pass_type_links_type_fk')
                ->references('id')->on('voucher_pass_types')->restrictOnDelete();
        });

        Schema::create('voucher_pass_funds', function (Blueprint $table) {
            $table->unsignedBigInteger('voucher_pass_id');
            $table->unsignedInteger('fund_id');

            $table->primary(['voucher_pass_id', 'fund_id'], 'voucher_pass_funds_primary');

            $table->foreign('voucher_pass_id', 'voucher_pass_funds_pass_fk')
                ->references('id')->on('voucher_passes')->restrictOnDelete();

            $table->foreign('fund_id', 'voucher_pass_funds_fund_fk')
                ->references('id')->on('funds')->restrictOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_pass_funds');
        Schema::dropIfExists('voucher_pass_type_links');
        Schema::dropIfExists('voucher_passes');
    }
};
