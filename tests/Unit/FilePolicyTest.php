<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Identity;
use App\Policies\FilePolicy;
use App\Services\FileService\Models\File;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FilePolicyTest extends TestCase
{
    /**
     * @return void
     */
    public function testFilesWithCsvUploadTypesCannotBeAccessed(): void
    {
        $identity = new Identity();
        $identity->forceFill(['address' => 'owner']);
        $identity->exists = true;
        $policy = resolve(FilePolicy::class);

        foreach (Employee::CSV_UPLOAD_FILE_TYPES as $fileType) {
            $file = new File();

            $file->forceFill([
                'type' => $fileType,
                'path' => '/files/ordinary/file.json',
                'identity_address' => $identity->address,
            ]);

            $this->assertFalse($policy->show($identity, $file));
            $this->assertFalse($policy->download($identity, $file));
        }
    }

    /**
     * @return void
     */
    public function testFilesStoredInCsvUploadDirectoryCannotBeAccessed(): void
    {
        Config::set('file.storage_path', 'files');

        $identity = new Identity();
        $identity->forceFill(['address' => 'owner']);
        $identity->exists = true;
        $policy = resolve(FilePolicy::class);

        $file = new File();

        $file->forceFill([
            'type' => 'unexpected_type',
            'path' => '/files/uploaded_csv_details/file.json',
            'identity_address' => $identity->address,
        ]);

        $this->assertFalse($policy->show($identity, $file));
        $this->assertFalse($policy->download($identity, $file));
    }

    /**
     * @return void
     */
    public function testOwnerCanAccessRegularFile(): void
    {
        Config::set('file.storage_path', 'files');

        $identity = new Identity();
        $identity->forceFill(['address' => 'owner']);
        $identity->exists = true;
        $policy = resolve(FilePolicy::class);

        $file = new File();

        $file->forceFill([
            'type' => 'reimbursement_proof',
            'path' => '/files/reimbursements/file.pdf',
            'identity_address' => $identity->address,
        ]);

        $this->assertTrue($policy->show($identity, $file));
        $this->assertTrue($policy->download($identity, $file));
    }
}
