<?php

use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $keysToMigrate = [
        'bi_connection' => 'App\Services\BIConnectionService\Models\BIConnection',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $enabled = DB::table('bi_connections')
            ->where('auth_type', '!=', 'disabled')
            ->pluck('id')
            ->all();

        Schema::table('bi_connections', function (Blueprint $table) {
            $table->dropColumn('auth_type');
            $table->boolean('enabled')->after('organization_id')->default(false);
        });

        DB::table('bi_connections')->whereIn('id', $enabled)->update([
            'enabled' => true,
        ]);

        foreach ($this->keysToMigrate as $type => $className) {
            /** @var EventLog[] $eventLogs */
            $eventLogs = EventLog::whereLoggableType($className)->get();

            foreach ($eventLogs as $eventLog) {
                $disabled = ($eventLog->data['bi_connection_auth_type'] ?? 'disabled') === 'disabled';
                $eventLog->forceFill([
                    'data->bi_connection_enabled' => !$disabled,
                    'loggable_type' => $type,
                ])->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('bi_connections', function (Blueprint $table) {
            $table->dropColumn('enabled');
            $table->enum('auth_type', ['disabled', 'header', 'parameter'])
                ->after('organization_id')
                ->default('disabled');
        });
    }
};
