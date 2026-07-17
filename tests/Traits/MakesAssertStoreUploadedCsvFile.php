<?php

namespace Tests\Traits;

use App\Models\Employee;
use App\Services\EventLogService\Models\EventLog;
use App\Services\FileService\Models\File;
use Illuminate\Support\Arr;

trait MakesAssertStoreUploadedCsvFile
{
    /**
     * @param Employee $employee
     * @param string $event
     * @param int $total
     * @return EventLog
     */
    protected function assertLogCreated(Employee $employee, string $event, int $total): EventLog
    {
        $logs = $employee->logs()->where('event', $event)->get();

        $this->assertEquals(1, $logs->count(), 'Event uploaded must be created');
        $this->assertEquals($total, Arr::get($logs[0]->data, 'uploaded_file_meta.total'));
        $this->assertEquals('success', Arr::get($logs[0]->data, 'uploaded_file_meta.state'));

        return $logs[0];
    }

    /**
     * @param EventLog $log
     * @param array $expected
     * @return void
     */
    protected function assertLoggedUploadedFileContent(EventLog $log, array $expected): void
    {
        $file = File::find(Arr::get($log->data, 'uploaded_file_meta.file_id'));
        $this->assertNotNull($file);
        $fileContent = json_decode(resolve('file')->getContent($file->path), true);

        $this->assertSame($expected, Arr::get($fileContent, 'data'));
    }
}
