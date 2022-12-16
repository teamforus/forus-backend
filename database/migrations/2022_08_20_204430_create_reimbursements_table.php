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
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('voucher_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->string('code', 20);
            $table->string('title', 200);
            $table->text('description');
            $table->decimal('amount');
            $table->text('reason');
            $table->string('iban', 100);
            $table->string('iban_name', 200);
            $table->enum('state', ['draft', 'pending', 'approved', 'declined'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('voucher_id')
                ->references('id')
                ->on('vouchers')
                ->onDelete('cascade');

            $table->foreign('employee_id')
                ->references('id')->on('employees')
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
        Schema::dropIfExists('reimbursements');
    }
};
