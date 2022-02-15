<?php

use App\Models\FundProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToFundProviders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->dropColumn('dismissed');
            $table->string('state', 20)
                ->default(FundProvider::STATE_PENDING)
                ->after('allow_products');
        });

        FundProvider::query()->update(['state' => FundProvider::STATE_APPROVED]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->dropColumn('state');
            $table->boolean('dismissed')->default(false)->after('allow_products');
        });
    }
}
