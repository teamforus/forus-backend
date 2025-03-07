<?php

namespace App\Services\FileService\Commands;

use Exception;
use Illuminate\Console\Command;

class FilesCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:cleanup
                            {--minutes= : How old in minutes should a file be to be considered expired.} 
                            {--force : Do not ask for confirmation before deleting.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $minutes = null;

        if ($this->hasOption('minutes')) {
            if (!is_numeric($this->option('minutes'))) {
                $this->error("Invalid argument `minutes`.\n");
                exit();
            }

            $minutes = intval($this->option('minutes'));
        }

        $this->filesWithoutFileable();
        $this->expiredFiles($minutes);
        $this->unusedFiles();
    }

    /**
     * @throws Exception
     */
    public function filesWithoutFileable(): void
    {
        $files = resolve('file');
        $countFiles = count($files->getFilesWithoutFileableList());

        if ($countFiles > 0) {
            echo sprintf("%s files without fileble where found.\n", $countFiles);

            if ($this->option('force') || $this->confirm('Would you like to delete them?')) {
                echo "√ {${$files->clearFilesWithoutFileable()}} files deleted.\n";
            } else {
                echo "√ Skipped.\n";
            }
        } else {
            echo "√ No files without fileble found.\n";
        }
    }

    /**
     * @param int $minutes
     * @throws Exception
     */
    public function expiredFiles(int $minutes): void
    {
        $files = resolve('file');
        $countFiles = count($files->getExpiredList($minutes ?: 5 * 60));

        if ($countFiles > 0) {
            echo sprintf("%s expired files found.\n", $countFiles);

            if ($this->option('force') ||
                $this->confirm('Would you like to delete them?')) {
                echo "√ {$files->clearExpiredFiles($minutes ?: 5 * 60)} files deleted.\n";
            } else {
                echo "√ Skipped.\n";
            }
        } else {
            echo "√ No expired files found.\n";
        }
    }

    /**
     * @throws Exception
     */
    public function unusedFiles(): void
    {
        $file = resolve('file');
        $countFiles = count($file->getUnusedFilesList());

        if ($countFiles > 0) {
            echo sprintf("%s unused files found.\n", $countFiles);

            if ($this->option('force') || $this->confirm('Would you like to delete them?')) {
                echo "√ {$file->clearStorage()} files deleted.\n";
            } else {
                echo "√ Skipped.\n";
            }
        } else {
            echo "√ No unused files found.\n";
        }
    }
}
