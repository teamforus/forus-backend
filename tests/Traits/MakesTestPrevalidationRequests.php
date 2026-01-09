<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Models\PrevalidationRequestRecord;
use Illuminate\Database\Eloquent\Builder;

trait MakesTestPrevalidationRequests
{
    /**
     * @param Organization $organization
     * @return void
     */
    protected function enablePrevalidationRequestForOrganization(Organization $organization): void
    {
        $organization->forceFill(['allow_prevalidation_requests' => true])->save();
    }

    /**
     * @param Fund $fund
     * @param array $data
     * @return PrevalidationRequest
     */
    protected function assertPrevalidationRequestCreated(Fund $fund, array $data): PrevalidationRequest
    {
        $request = PrevalidationRequest::where('fund_id', $fund->id)
            ->whereHas('records', function (Builder $builder) use ($data) {
                $builder->where('record_type_key', 'uid');
                $builder->where('value', $data['uid']);
            })
            ->first();

        $this->assertNotNull($request);
        $this->assertRecordsEquals($request, $data);

        return $request;
    }

    /**
     * @param PrevalidationRequest $request
     * @param array $data
     * @return void
     */
    protected function assertRecordsEquals(PrevalidationRequest $request, array $data): void
    {
        $records = $request->records;

        foreach ($data as $field => $value) {
            $record = $records->first(fn (PrevalidationRequestRecord $record) => $record->record_type_key === $field);
            $this->assertNotNull($record);
            $this->assertEquals($value, $record->value);
        }
    }
}
