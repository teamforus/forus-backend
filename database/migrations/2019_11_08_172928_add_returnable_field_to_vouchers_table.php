<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Voucher;

/**
 * @noinspection PhpUnused
 */
class AddReturnableFieldToVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->boolean('returnable')->default(true)->after('amount');
        });

        Voucher::whereNull('parent_id')->update([
            'returnable' => false
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->dropColumn('returnable');
        });
    }
}
