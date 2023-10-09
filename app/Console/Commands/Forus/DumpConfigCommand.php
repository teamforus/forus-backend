<?php

namespace App\Console\Commands\Forus;

use App\Console\Commands\BaseCommand;
use App\Helpers\Validation;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class DumpConfigCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dump:config
                            {--action= : target action.}
                            {--email= : target email.}
                            {--url= : target url.}
                            {--frontend= : target frontend.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Config dump database for local usage.';


    protected string $action;

    public const ACTION_ADD_OWNER = 'action_add_owner';
    public const ACTION_GENERATE_MEDIA = 'action_generate_media';
    public const ACTION_UPDATE_FRONTENDS = 'action_add_employee';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle(): void
    {
        Config::set('mail.disable', true);

        $this->printHeader("Dump config tool", 2);

        if (App::isProduction()) {
            $this->error('This command is not allowed on production environment.');
            return;
        }

        $this->askAction();
    }

    /**
     * @return array
     */
    protected function askActionList(): array
    {
        return [
            '[1] Update all organizations owner.',
            '[2] Update implementations frontend urls.',
            '[3] Generate and replace all images.',
            '[4] Exit',
        ];
    }

    /**
     * @return array
     */
    protected function askFrontendTypeList(): array
    {
        return [
            '[1] Webshop.',
            '[2] Sponsor dashboard.',
            '[3] Provider dashboard.',
            '[4] Validator dashboard.',
            '[5] Exit',
        ];
    }

    /**
     * @return void
     * @throws \Throwable
     */
    protected function askAction(): void
    {
        $actionCli = $this->getOption('action');

        if (!$actionCli) {
            $this->printHeader("Select next action:");
            $this->printList($this->askActionList());
            $action = $this->ask("Please select next step:", 1);
        }

        switch ($actionCli ?: $action) {
            case 1: $this->addOwner(); break;
            case 2: $this->updateFrontends(); break;
            case 3: $this->generateMedia(!!$actionCli); break;
            case 4: $this->exit();
            default: $this->printText("Invalid input!\nPlease try again:\n");
        }

        if (!$actionCli) {
            $this->askAction();
        }
    }

    /**
     * @return void
     */
    protected function addOwner(): void
    {
        Config::set('queue.default', 'sync');

        do {
            $emailCli = $this->getOption('email');
            $email = $emailCli ?: $this->ask("Identity email (or type cancel)");
            $isValid = Validation::check($email,'required|email')->passes() || $email == 'cancel';

            if (!$isValid) {
                $this->warn("Invalid email provided, please try again.");
                $this->printSeparator();
            }
        } while(!$isValid);

        if ($email == 'cancel') {
            return;
        }

        $identity = Identity::findByEmail($email) ?: Identity::make($email);

        $this->withProgressBar(Organization::get(), function (Organization $organization) use ($identity) {
            $organization->addEmployee($identity, Role::pluck('id')->toArray());
            $organization->update(['identity_address' => $identity->address]);
        });

        $this->info("\n\nDone!");
        $this->printSeparator();
    }

    /**
     * @return void
     */
    protected function updateFrontends(): void
    {
        do {
            $frontend = $this->getOption('frontend');

            if (!$frontend) {
                $this->printList($this->askFrontendTypeList());
                $frontend = $this->ask("Please select frontend type", 1);
            }

            $frontend = [
                1 => 'url_webshop',
                2 => 'url_sponsor',
                3 => 'url_provider',
                4 => 'url_validator',
                5 => 'exit',
            ][$frontend] ?? null;

            if (!$frontend) {
                $this->warn("Invalid value selected, please try again.");
                $this->printSeparator();
                continue;
            }

            if ($frontend == 'exit') {
                break;
            }

            $frontendUrlOption = $this->getOption('url');
            $frontendUrl = $frontendUrlOption ?: $this->ask("Please provide the new `$frontend` value");

            $this->withProgressBar(Implementation::get(), function (
                Implementation $implementation
            ) use ($frontend, $frontendUrl) {
                $implementation->update([$frontend => $frontendUrl]);
            });

            $this->info("\n\nDone!");
            $this->printSeparator();

            if ($frontendUrlOption) {
                break;
            }
        } while(true);
    }

    /**
     * @param bool $quiet
     * @return void
     * @throws \Exception
     */
    protected function generateMedia(bool $quiet = false): void
    {
        /** @var MediaService $media */
        $mediaService = resolve('media');

        if (!$quiet && !$this->confirm('This action might take a considerable amount of time. Continue?')) {
            return;
        }

        $media = Media::get();
        $files = [];

        $this->printHeader('Step 1 of 3: replace original media paths');
        $this->withProgressBar($media, function(Media $media) use (&$files) {
            $path = '/media/' . token_generator()->generate(8, 4) . '.jpeg';
            $media->size_original()->updateOrCreate([
                'key' => 'original',
                'media_id' => $media->id,
            ], [
                'path' => $path,
            ]);

            $files[] = $path;
        });

        $this->printText("\n");
        $this->printSeparator();

        $this->printHeader('Step 2 of 3: copy images to the new paths');
        $this->withProgressBar($files, function(string $file) use ($mediaService) {
            $sourceFile = str_pad(random_int(1, 10), 3, '0', STR_PAD_LEFT);
            $mediaService->storage()->put($file, file_get_contents(storage_path("/dump-assets/$sourceFile.jpg")));
        });

        $this->printText("\n");
        $this->printSeparator();

        $this->printHeader('Step 3 of 3: generate all media presets');

        $this->withProgressBar($media, function(Media $media) use ($mediaService) {
            $mediaService->regenerateMedia(MediaService::getMediaConfig($media->type), $media);
        });

        $this->info("\n\nDone!");
        $this->printSeparator();

        if (!$quiet && $this->confirm(implode(' ', [
            'This action (especially when run multiple times) might leave a lot of unused files on the disk.?',
            'Do you want to perform "media:cleanup" command?',
        ]))) {
            $this->call('media:cleanup', ['--minutes' => 60]);
            $this->info("\n\nDone!");
            $this->printSeparator();
        }
    }
}
