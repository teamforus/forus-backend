<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Implementation;
use App\Services\Forus\Notification\Repositories\NotificationRepo;

class NotificationTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Throwable
     */
    public function run(): void
    {
        $notificationsRepo = resolve(NotificationRepo::class);
        $data = file_get_contents(database_path('seeders/resources/mail_templates/notification_templates.json'));
        $data = json_decode($data, true);
        $generalImplementation = Implementation::general();
        $communicationTypes = ['formal', 'informal'];

        if (is_null($data)) {
            echo "Could not parse the notification_templates.json file!\n";
            return;
        }

        foreach ($notificationsRepo->getSystemNotifications() as $systemNotification) {
            $notificationData = $data[$systemNotification->key] ?? null;

            if (is_null($notificationData)) {
                echo "Notification config data for: $systemNotification->key not found!\n";
                continue;
            }

            foreach ($notificationData as $type => $template) {
                foreach ($communicationTypes as $communicationType) {
                    $keySuffix = $communicationType == 'informal' ? '_informal' : '';
                    $title = $template["title$keySuffix"] ?? $template["title"] ?? '';
                    $templatePath = $template["template$keySuffix"] ?? $template["template"] ?? null;

                    if ($type === 'mail' && $templatePath) {
                        $templatePath = database_path('seeders/resources/mail_templates/' . $templatePath);
                        $content = file_exists($templatePath) ? file_get_contents($templatePath) : '';
                    } else {
                        $content = $template["content$keySuffix"] ?? $template["content"] ?? '';
                    }

                    $systemNotification->templates()->updateOrCreate([
                        'type' => $type,
                        'formal' => $communicationType == 'formal',
                        'implementation_id' => $generalImplementation->id,
                    ], compact('title', 'content'));
                }
            }
        }
    }
}
