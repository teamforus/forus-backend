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
        Schema::create('prevalidation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('bsn');
            $table->enum('state', ['pending', 'success', 'fail'])->default('pending');
            $table->unsignedInteger('organization_id')->unsigned();
            $table->unsignedInteger('employee_id')->nullable();
            $table->unsignedInteger('fund_id');
            $table->unsignedInteger('prevalidation_id')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('restrict');

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('set null');

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('restrict');

            $table->foreign('prevalidation_id')
                ->references('id')
                ->on('prevalidations')
                ->onDelete('set null');
        });

        Schema::table('prevalidations', function (Blueprint $table) {
            $table->unsignedBigInteger('prevalidation_request_id')->nullable()->after('organization_id');

            $table->foreign('prevalidation_request_id')
                ->references('id')
                ->on('prevalidation_requests')
                ->onDelete('set null');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('allow_prevalidation_requests')->default(false)->after('allow_product_updates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('allow_prevalidation_requests');
        });

        Schema::table('prevalidations', function (Blueprint $table) {
            $table->dropForeign(['prevalidation_request_id']);
            $table->dropColumn('prevalidation_request_id');
        });

        Schema::dropIfExists('prevalidation_requests');
    }
};
