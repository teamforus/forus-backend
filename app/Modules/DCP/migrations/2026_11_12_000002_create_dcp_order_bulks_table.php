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
        Schema::create('dcp_order_bulks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->timestamp('approved_at');
            $table->timestamps();

            $table->index(['organization_id', 'id'], 'dcp_order_bulks_organization_id_index');

            $table->foreign('organization_id', 'dcp_order_bulks_organization_fk')
                ->references('id')->on('organizations')->restrictOnDelete();

            $table->foreign('employee_id', 'dcp_order_bulks_employee_fk')
                ->references('id')->on('employees')->nullOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('dcp_order_bulks');
    }
};
