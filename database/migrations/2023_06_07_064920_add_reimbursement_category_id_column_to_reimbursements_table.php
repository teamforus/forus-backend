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
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->unsignedBigInteger('reimbursement_category_id')->nullable()->after('provider_name');

            $table->foreign('reimbursement_category_id')
                ->references('id')->on('reimbursement_categories')
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
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropForeign('reimbursements_reimbursement_category_id_foreign');
            $table->dropColumn('reimbursement_category_id');
        });
    }
};
