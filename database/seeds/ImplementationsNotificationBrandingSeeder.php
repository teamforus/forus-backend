<?php

use Illuminate\Database\Seeder;
use App\Models\Implementation;

class ImplementationsNotificationBrandingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        $this->migrateImplementation(
            'general',
            '#315EFD',
            database_path('/seeds/resources/mail_assets/general-logo.jpg')
        );

        $this->migrateImplementation(
            'nijmegen',
            '#EB0029',
            database_path('/seeds/resources/mail_assets/nijmegen-logo.jpg')
        );

        $this->migrateImplementation(
            'groningen',
            '#F6F5F5',
            database_path('/seeds/resources/mail_assets/groningen-logo.jpg')
        );
    }

    /**
     * @param string $implementation_key
     * @param string $email_color
     * @param string $logo_path
     * @throws Exception
     */
    public function migrateImplementation(
        string $implementation_key,
        string $email_color,
        string $logo_path
    ): void {
        $implementation = Implementation::byKey($implementation_key);

        if (!$implementation) {
            return;
        }

        $implementation->update(compact('email_color'));

        if (file_exists($logo_path)) {
            $media = resolve('media')->uploadSingle($logo_path, 'auth_icon.jpg', 'email_logo');
            $implementation->attachMediaByUid($media->uid);
        }
    }
}
