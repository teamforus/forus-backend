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
        Schema::create('voucher_pass_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id');
            $table->string('name', 200)->charset('utf8mb4')->collation('utf8mb4_bin');
            $table->timestamps();

            $table->unique(['organization_id', 'name'], 'voucher_pass_types_organization_name_unique');

            $table->foreign('organization_id', 'voucher_pass_types_organization_fk')
                ->references('id')->on('organizations')->restrictOnDelete();
        });

        Schema::create('voucher_pass_type_funds', function (Blueprint $table) {
            $table->unsignedBigInteger('voucher_pass_type_id');
            $table->unsignedInteger('fund_id');

            $table->primary(['voucher_pass_type_id', 'fund_id'], 'voucher_pass_type_funds_primary');

            $table->foreign('voucher_pass_type_id', 'voucher_pass_type_funds_type_fk')
                ->references('id')->on('voucher_pass_types')->restrictOnDelete();

            $table->foreign('fund_id', 'voucher_pass_type_funds_fund_fk')
                ->references('id')->on('funds')->restrictOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_pass_type_funds');
        Schema::dropIfExists('voucher_pass_types');
    }
};
