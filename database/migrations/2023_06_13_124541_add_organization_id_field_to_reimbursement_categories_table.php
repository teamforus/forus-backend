<?php

use App\Models\ReimbursementCategory;
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
        ReimbursementCategory::query()->delete();

        Schema::table('reimbursement_categories', function (Blueprint $table) {
            $table->integer('organization_id')->unsigned()->after('id');

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
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
        Schema::table('reimbursement_categories', function (Blueprint $table) {
            $table->dropForeign('reimbursement_categories_organization_id_foreign');
            $table->dropColumn('organization_id');
        });
    }
};
