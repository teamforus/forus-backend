<?php

namespace Database\Seeders;

use App\Models\Implementation;
use App\Models\NotificationTemplate;
use App\Models\SystemNotification;
use App\Services\Forus\Notification\Repositories\NotificationRepo;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Throwable;

class NotificationTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws Throwable
     * @return void
     */
    public function run(): void
    {
        $this->execute();
    }

    /**
     * @param Command|null $command
     * @param bool $force
     * @return void
     */
    public function execute(?Command $command = null, bool $force = false): void
    {
        $notificationsRepo = resolve(NotificationRepo::class);
        $data = file_get_contents(database_path('seeders/resources/mail_templates/notification_templates.json'));
        $data = json_decode($data, true);
        $generalImplementation = Implementation::general();
        $communicationTypes = ['formal', 'informal'];
        $updatedTemplates = [];

        if (is_null($data)) {
            if (!App::runningUnitTests()) {
                echo "Could not parse the notification_templates.json file!\n";
            }

            return;
        }

        foreach ($notificationsRepo->getSystemNotifications() as $notification) {
            $notificationData = $data[$notification->key] ?? null;

            if (is_null($notificationData)) {
                if (!App::runningUnitTests()) {
                    echo "Notification config data for: $notification->key not found!\n";
                }

                continue;
            }

            foreach ($notificationData as $type => $template) {
                foreach ($communicationTypes as $communicationType) {
                    $keySuffix = $communicationType == 'informal' ? '_informal' : '';
                    $title = $template["title$keySuffix"] ?? $template['title'] ?? '';
                    $formal = $communicationType == 'formal';
                    $templatePath = $template["template$keySuffix"] ?? $template['template'] ?? null;

                    if ($type === 'mail' && $templatePath) {
                        $templatePath = database_path('seeders/resources/mail_templates/' . $templatePath);
                        $content = file_exists($templatePath) ? file_get_contents($templatePath) : '';
                    } else {
                        $content = $template["content$keySuffix"] ?? $template['content'] ?? '';
                    }

                    $templateData = [
                        'type' => $type,
                        'formal' => $formal,
                        'implementation_id' => $generalImplementation->id,
                    ];

                    $templateModel = (clone $notification->templates())->firstWhere($templateData);

                    if (!$templateModel) {
                        $notification->templates()->create([
                            ...$templateData,
                            'title' => $title,
                            'content' => $content,
                        ]);
                        continue;
                    }

                    $templateChanged =
                        trim($templateModel->title) !== trim($title) ||
                        trim($templateModel->content) !== trim($content);

                    $overview = implode(', ', [
                        "id: $templateModel->id",
                        "key: $notification->key",
                        "type: $templateModel->type",
                        'formality: ' . ($templateModel->formal ? 'formal' : 'informal'),
                    ]);

                    if ($templateChanged && (!$command || $force || $this->confirmUpdate($command, $overview))) {
                        $templateModel->update([
                            'title' => $title,
                            'content' => $content,
                        ]);

                        $updatedTemplates[] = $overview;
                    }
                }
            }
        }

        if (count($updatedTemplates) > 0) {
            $command?->info('Following templates have been updated:');
            $command?->info(implode("\n", $updatedTemplates));
        } else {
            $command?->info('All templates up to date!');
        }
    }

    /**
     * @param Command $command
     * @param string $overview
     * @return bool
     */
    protected function confirmUpdate(Command $command, string $overview): bool
    {
        $command->info("The following default template have been changed:");
        $command->info($overview);

        return $command->confirm(" Would you like to update?");
    }
}
