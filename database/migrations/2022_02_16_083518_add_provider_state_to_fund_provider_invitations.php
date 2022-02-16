<?php

use App\Models\FundProvider;
use App\Models\FundProviderInvitation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderStateToFundProviderInvitations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_provider_invitations', function (Blueprint $table) {
            $table->string('provider_state', 20)
                ->default(FundProvider::STATE_PENDING)
                ->after('token');
        });

        FundProviderInvitation::query()->update(['provider_state' => FundProvider::STATE_APPROVED]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_provider_invitations', function (Blueprint $table) {
            $table->dropColumn('provider_state');
        });
    }
}
