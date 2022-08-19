<?php

use App\Models\VoucherTransaction;
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
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->enum('target', VoucherTransaction::TARGETS)
                ->default(VoucherTransaction::TARGET_PROVIDER)
                ->after('initiator');
            $table->string('target_iban', 200)->nullable()->after('target');

            $table->unsignedInteger('organization_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex('voucher_transactions_organization_id_foreign');
        });

        VoucherTransaction::whereNull('organization_id')->delete();

        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->dropColumn(['target', 'target_iban']);
            $table->unsignedInteger('organization_id')->nullable(false)->change();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');
        });
    }
};
