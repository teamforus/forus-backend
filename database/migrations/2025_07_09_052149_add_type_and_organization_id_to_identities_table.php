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
        Schema::table('identities', function (Blueprint $table) {
            $table->integer('creator_organization_id')
                ->unsigned()
                ->nullable()
                ->after('address');

            $table->integer('creator_employee_id')
                ->unsigned()
                ->nullable()
                ->after('creator_organization_id');

            $table->enum('type', ['profile', 'voucher', 'employee'])
                ->default(null)
                ->nullable()
                ->after('creator_employee_id');

            $table->foreign('creator_organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('restrict');

            $table->foreign('creator_employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('identities', function (Blueprint $table) {
            $table->dropForeign('identities_creator_organization_id_foreign');
            $table->dropForeign('identities_creator_employee_id_foreign');

            $table->dropColumn('creator_organization_id');
            $table->dropColumn('creator_employee_id');
            $table->dropColumn('type');
        });
    }
};
