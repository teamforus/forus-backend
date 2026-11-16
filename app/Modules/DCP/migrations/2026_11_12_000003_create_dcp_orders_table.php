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
        Schema::create('dcp_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id');
            $table->unsignedBigInteger('dcp_connection_id');
            $table->unsignedBigInteger('dcp_order_bulk_id')->nullable();
            $table->unsignedInteger('employee_id')->nullable();
            $table->string('orderable_type', 100);
            $table->unsignedBigInteger('orderable_id');
            $table->string('order_id', 100)->unique('dcp_orders_order_id_unique');
            $table->string('order_id_dcp', 100)->collation('utf8mb4_bin')->nullable();
            $table->string('label', 200);
            $table->string('delivery_first_name', 200);
            $table->string('delivery_last_name', 200);
            $table->string('delivery_address1', 400);
            $table->string('delivery_postal_code', 20);
            $table->string('delivery_city', 200);
            $table->string('delivery_country', 200);
            $table->json('order_rows');
            $table->enum('state', [
                'draft', 'approved', 'sending', 'accepted', 'rejected', 'unknown', 'closed', 'canceled', 'blocked',
            ])->default('draft');
            $table->integer('status_id')->nullable();
            $table->text('status_message')->nullable();
            $table->string('diagnostic_id', 36)->nullable();
            $table->json('submission_response')->nullable();
            $table->json('status_response')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sending_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_status_checked_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();

            $table->unique(['dcp_connection_id', 'orderable_type', 'orderable_id'], 'dcp_orders_source_unique');
            $table->unique(['dcp_connection_id', 'order_id_dcp'], 'dcp_orders_remote_id_unique');
            $table->index(['orderable_type', 'orderable_id'], 'dcp_orders_orderable_index');
            $table->index(['organization_id', 'state', 'id'], 'dcp_orders_organization_state_index');
            $table->index(['dcp_order_bulk_id', 'state'], 'dcp_orders_bulk_state_index');
            $table->index(['state', 'id'], 'dcp_orders_send_index');
            $table->index(['state', 'last_status_checked_at', 'id'], 'dcp_orders_status_check_index');

            $table->foreign('organization_id', 'dcp_orders_organization_fk')
                ->references('id')->on('organizations')->restrictOnDelete();

            $table->foreign('dcp_connection_id', 'dcp_orders_connection_fk')
                ->references('id')->on('dcp_connections')->restrictOnDelete();

            $table->foreign('dcp_order_bulk_id', 'dcp_orders_bulk_fk')
                ->references('id')->on('dcp_order_bulks')->restrictOnDelete();

            $table->foreign('employee_id', 'dcp_orders_employee_fk')
                ->references('id')->on('employees')->nullOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('dcp_orders');
    }
};
