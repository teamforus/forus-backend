<?php

namespace App\Services\MailDatabaseLoggerService\Commands;

use App\Console\Commands\BaseCommand;
use App\Services\MailDatabaseLoggerService\MailDatabaseLogger;
use App\Services\MailDatabaseLoggerService\Models\EmailLogAttachment;

class MailDatabaseLoggerClearUnusedAttachmentsCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail-database-logger:clear-unused-attachments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all unused emails log attachments';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $service = MailDatabaseLogger::make();
        $storage = $service->storage();
        $directories = $storage->directories($service->getStoragePath());

        $this->withProgressBar($directories, function (string $directory) use ($storage) {
            $directoryFiles = $storage->allFiles($directory);

            foreach ($directoryFiles as $directoryFile) {
                if (!EmailLogAttachment::wherePath($directoryFile)->exists()) {
                    $storage->delete($directoryFile);
                }
            }
        });
    }
}
