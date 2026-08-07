<?php

namespace Tests\Unit;

use App\Http\Resources\PrevalidationRequestRecordResource;
use App\Models\PrevalidationRequestRecord;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PrevalidationRequestRecordResourceTest extends TestCase
{
    /**
     * @return void
     */
    public function testFallsBackToRecordTypeKeyWhenRecordTypeIsMissing(): void
    {
        $record = (new PrevalidationRequestRecord())->forceFill([
            'id' => 1,
            'prevalidation_request_id' => 2,
            'record_type_key' => 'unknown_brp_record_key',
            'value' => 'unknown value',
            'source' => PrevalidationRequestRecord::SOURCE_BRP,
        ]);

        $record->setRelation('logs', new Collection());
        $record->setRelation('record_type', null);

        $data = (new PrevalidationRequestRecordResource($record))->toArray(request());

        $this->assertSame('unknown_brp_record_key', $data['record_type']['key']);
        $this->assertSame('unknown_brp_record_key', $data['record_type']['name']);
        $this->assertNull($data['record_type']['type']);
        $this->assertSame([], $data['record_type']['options']);
    }
}
