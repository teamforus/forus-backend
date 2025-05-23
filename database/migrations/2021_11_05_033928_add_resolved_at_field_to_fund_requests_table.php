<?php

use App\Models\FundRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dateTime('resolved_at')->nullable()->after('state');
        });

        $fundRequests = FundRequest::query()
            ->whereNull('resolved_at')
            ->whereIn('state', ['approved', 'declined', 'disregarded', 'approved_partly'])
            ->with('logs')
            ->get();

        foreach ($fundRequests as $fundRequest) {
            $eventLog = $fundRequest->logs->where('event', $fundRequest::EVENT_RESOLVED)[0] ?? null;

            $fundRequest->fill([
                'resolved_at' => $eventLog->created_at ?? $fundRequest->updated_at,
            ])->save([
                'timestamps' => false,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropColumn('resolved_at');
        });
    }
};
