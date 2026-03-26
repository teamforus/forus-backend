<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prevalidation_requests', function (Blueprint $table) {
            $table->timestamp('fetched_date')->nullable()->after('fund_id');
        });

        DB::table('prevalidation_requests')
            ->get()
            ->each(function ($prevalidation_request) {
                DB::table('prevalidation_requests')
                    ->where('id', $prevalidation_request->id)
                    ->update(['fetched_date' => $prevalidation_request->created_at]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prevalidation_requests', function (Blueprint $table) {
            $table->dropColumn('fetched_date');
        });
    }
};
