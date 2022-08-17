<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Fund;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        foreach (Fund::whereDoesntHave('fund_config')->get() as $fund) {
            $fund->makeFundConfig();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
