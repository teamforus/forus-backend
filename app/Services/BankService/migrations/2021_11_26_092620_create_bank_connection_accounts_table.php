<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BankConnection;

/**
 * @noinspection PhpUnused
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class CreateBankConnectionAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('bank_connection_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('bank_connection_id');
            $table->string('monetary_account_id');
            $table->string('monetary_account_iban');
            $table->string('type', 200)->default('monetary');
            $table->timestamps();
        });

        Schema::table('bank_connections', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_connection_account_id')->nullable()
                ->after('implementation_id');

            $table->foreign('bank_connection_account_id')
                ->references('id')->on('bank_connection_accounts')
                ->onDelete('RESTRICT');
        });

        $bankConnections = BankConnection::whereNotIn('state', [
            BankConnection::STATE_PENDING,
            BankConnection::STATE_REJECTED,
        ])->get();

        foreach ($bankConnections as $bankConnection) {
            /** @var \App\Models\BankConnectionAccount $connectionAccount */
            $connectionAccount = $bankConnection->bank_connection_accounts()->create($bankConnection->only([
                'monetary_account_id', 'monetary_account_iban',
            ]));

            $bankConnection->update([
                'bank_connection_account_id' => $connectionAccount->id,
            ]);
        }

        Schema::table('bank_connections', function (Blueprint $table) {
            $table->dropColumn('monetary_account_id', 'monetary_account_iban');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->string('monetary_account_id');
            $table->string('monetary_account_iban');
        });

        $bankConnections = BankConnection::whereHas('bank_connection_default_account')->get();

        foreach ($bankConnections as $bankConnection) {
            $bankConnection->forceFill($bankConnection->bank_connection_default_account->only([
                'monetary_account_id', 'monetary_account_iban',
            ]))->save();
        }

        Schema::table('bank_connections', function (Blueprint $table) {
            $table->dropForeign('bank_connections_bank_connection_account_id_foreign');
            $table->dropColumn('bank_connection_account_id');
        });

        Schema::dropIfExists('bank_connection_accounts');
    }
}
