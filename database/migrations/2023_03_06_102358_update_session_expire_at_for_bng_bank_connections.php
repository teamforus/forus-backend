<?php

use App\Models\BankConnection;
use App\Services\BankService\Models\Bank;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $connections = BankConnection::query()
            ->whereNull('expire_at')
            ->whereRelation('bank', 'key', Bank::BANK_BNG)
            ->get();

        $connections->each(fn (BankConnection $connection) => $connection->update([
            'expire_at' => $connection->created_at->addMonths(6)->subWeek(),
        ]));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
