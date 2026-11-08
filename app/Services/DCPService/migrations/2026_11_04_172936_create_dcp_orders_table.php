<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('dcp_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('organization_id');
            $table->unsignedBigInteger('dcp_order_bulk_id')->nullable();
            $table->unsignedBigInteger('dcp_connection_id')->nullable();
            $table->string('orderable_type', 100);
            $table->unsignedBigInteger('orderable_id');
            $table->string('order_id', 100)->unique();
            $table->string('order_id_dcp', 100)->nullable();
            $table->enum('state', ['pending', 'sent', 'error', 'closed', 'canceled'])->default('pending');
            $table->integer('status_id')->nullable();
            $table->string('status_message', 1000)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('payload_edited_at')->nullable();
            $table->unsignedInteger('payload_edited_by_employee_id')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->string('response_code', 10)->nullable();
            $table->json('response_body')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();

            $table->index(['orderable_type', 'orderable_id']);

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

            $table->foreign('dcp_order_bulk_id')
                ->references('id')
                ->on('dcp_order_bulks')
                ->onDelete('set null');

            $table->foreign('payload_edited_by_employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('set null');

            $table->foreign('dcp_connection_id')
                ->references('id')
                ->on('dcp_connections')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('dcp_orders');
    }
};
