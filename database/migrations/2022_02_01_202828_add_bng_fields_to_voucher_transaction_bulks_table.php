<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddBngFieldsToVoucherTransactionBulksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('voucher_transaction_bulks', function (Blueprint $table) {
            $table->string('payment_id', 200)->nullable()->change();
            $table->string('monetary_account_id', 200)->nullable()->change();
            $table->string('monetary_account_name', 200)->nullable()->after('monetary_account_iban');
            $table->string('code', 200)->nullable()->after('monetary_account_name');
            $table->string('access_token', 200)->nullable()->after('code');
            $table->string('redirect_token', 200)->nullable()->after('access_token');
            $table->string('auth_url', 1000)->nullable()->after('redirect_token');
            $table->json('auth_params')->nullable()->after('auth_url');
            $table->text('sepa_xml')->nullable()->after('auth_url');
            $table->date('execution_date')->nullable()->after('sepa_xml');
            $table->integer('implementation_id')->unsigned()->nullable()->after('execution_date');

            $table->foreign('implementation_id')->references('id')
                ->on('implementations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('voucher_transaction_bulks', function (Blueprint $table) {
            $table->dropForeign('voucher_transaction_bulks_implementation_id_foreign');
            $table->removeColumn('voucher_transaction_bulks_implementation_id_foreign');

            $table->dropColumn([
                'code', 'auth_url', 'auth_params', 'monetary_account_name', 'sepa_xml',
                'access_token', 'redirect_token', 'execution_date', 'implementation_id',
            ]);
        });
    }
}
