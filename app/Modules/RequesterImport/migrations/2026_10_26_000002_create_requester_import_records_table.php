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
        Schema::create('requester_import_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_import_row_id');
            $table->unsignedInteger('organization_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->string('record_type_key', 200);
            $table->string('value', 400);
            $table->timestamp('synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unsignedTinyInteger('current_version')->nullable()
                ->storedAs('CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END');

            $table->string('current_client_number', 400)->charset('utf8mb4')->collation('utf8mb4_bin')->nullable()
                ->storedAs("CASE WHEN deleted_at IS NULL AND record_type_key = 'client_number' THEN value ELSE NULL END");

            $table->unique(
                ['requester_import_row_id', 'record_type_key', 'current_version'],
                'requester_records_current_type_unique',
            );

            $table->unique(['organization_id', 'current_client_number'], 'requester_records_client_number_unique');

            $table->foreign(['requester_import_row_id', 'organization_id'], 'requester_records_row_organization_foreign')
                ->references(['id', 'organization_id'])->on('requester_import_rows')->restrictOnDelete();

            $table->foreign('record_type_key')->references('key')->on('record_types')->restrictOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('requester_import_records');
    }
};
