<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\FileService\Models\File;
use App\Services\EventLogService\Models\EventLog;

/**
 * @noinspection PhpUnused
 */
class MigrateMorphKeysForFilesAndEventLogs extends Migration
{
    protected array $keysToMigrate = [
        'fund_request_record' => 'App\Models\FundRequestRecord',
        'fund_request_clarification' => 'App\Models\FundRequestClarification',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        foreach ($this->keysToMigrate as $type => $className) {
            File::whereFileableType($className)->update([
                'fileable_type' => $type,
            ]);

            EventLog::whereLoggableType($className)->update([
                'loggable_type' => $type,
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
        foreach ($this->keysToMigrate as $type => $className) {
            File::whereFileableType($type)->update([
                'fileable_type' => $className,
            ]);

            EventLog::whereLoggableType($type)->update([
                'loggable_type' => $className,
            ]);
        }
    }
}
