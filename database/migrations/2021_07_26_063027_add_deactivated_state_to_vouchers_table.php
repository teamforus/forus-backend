<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Voucher;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->string('state_tmp', 200)->default('active')->after('identity_address');
        });

        Voucher::query()->update([
            'state_tmp' => DB::raw('state'),
        ]);

        Schema::table('vouchers', function(Blueprint $table) {
            $table->dropColumn('state');
        });

        Schema::table('vouchers', function(Blueprint $table) {
            $table->renameColumn('state_tmp', 'state');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
