<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('show_subsidies')->after('reimbursement_approve_offset')->default(false);
            $table->boolean('show_qr_limits')->after('show_subsidies')->default(false);
            $table->boolean('show_requester_limits')->after('show_qr_limits')->default(false);
        });

        DB::table('fund_configs')
            ->whereIn('fund_id', function ($query) {
                $query->select('id')
                    ->from('funds')
                    ->whereIn('organization_id', function ($query) {
                        $query->select('id')
                            ->from('organizations')
                            ->where('allow_budget_fund_limits', 1);
                    });
            })
            ->update([
                'show_requester_limits' => 1,
            ]);

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('allow_budget_fund_limits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('allow_budget_fund_limits')->after('allow_custom_fund_notifications')->default(false);
        });

        DB::table('organizations')
            ->whereIn('id', function ($query) {
                $query->select('organization_id')
                    ->from('funds')
                    ->whereIn('id', function ($query) {
                        $query->select('fund_id')
                            ->from('fund_configs')
                            ->where('show_requester_limits', 1);
                    });
            })
            ->update(['allow_budget_fund_limits' => 1]);

        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('show_subsidies');
            $table->dropColumn('show_qr_limits');
            $table->dropColumn('show_requester_limits');
        });
    }
};
