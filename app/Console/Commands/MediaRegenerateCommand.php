<?php

namespace App\Console\Commands;

use App\Services\MediaService\MediaService;
use Illuminate\Console\Command;

class MediaRegenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:regenerate 
                            {media_type? : Name for the config to be regenerated or `all` to regenerate all media.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate media files largest preset';
}
