<?php

namespace App\Http\Controllers;

use App\Http\Requests\DigID\ResolveDigIdRequest;
use App\Http\Requests\DigID\StartDigIdRequest;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Prevalidation;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Database\Eloquent\Builder;

class DigIdController extends Controller
{

    protected $recordRepo;

    /**
     * DigIdController constructor.
     * @param IRecordRepo $recordRepo
     */
    public function __construct(IRecordRepo $recordRepo)
    {
        $this->recordRepo = $recordRepo;
    }

    /**
     * @param StartDigIdRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     * @throws \Exception
     */
    public function start(
        StartDigIdRequest $request
    ) {
        $fund = Fund::find($request->input('fund_id'));

        /** @var Implementation $implementation */
        $implementation = Implementation::where([
            'key' => Implementation::activeKey()
        ])->first();

        if (!empty($this->recordRepo->bsnByAddress(auth_address()))) {
            return abort(403, 'BSN is already known.');
        }

        if (!$implementation || !$implementation->digidEnabled()) {
            return abort(501, 'Invalid implementation or no DigId support.');
        }

        if (!$fund->fund_config && $implementation->id !=
            $fund->fund_config->implementation_id) {
            return abort(501, 'Target fund is not part of current implementation.');
        }

        $digId = $implementation->getDigid();
        $goBackUrl = $implementation->funds[0]->urlWebshop(sprintf(
            '/fund/%s/request', $request->input('fund_id')
        )) . '?digid=true';

        if ($redirectUrl = $digId->makeAuthRequestUrl($goBackUrl)) {
            return compact('redirectUrl');
        }

        return abort(501);
    }

    /**
     * @param ResolveDigIdRequest $request
     * @return array|void
     * @throws \Exception
     */
    public function resolve(
        ResolveDigIdRequest $request
    ) {
        /** @var Implementation $implementation */
        $implementation = Implementation::where([
            'key' => Implementation::activeKey()
        ])->first();

        if (!empty($this->recordRepo->bsnByAddress(auth_address()))) {
            return abort(403, 'BSN is already known.');
        }

        if (!$implementation || !$implementation->digidEnabled()) {
            return abort(501, 'Invalid implementation or no DigId support.');
        }

        $bsn = $implementation->getDigid()->getBsnFromResponse(
            $request->input('rid'),
            $request->input('aselect_credentials')
        );

        $this->recordRepo->recordCreate(auth_address(), 'bsn', $bsn);
        $record_type_id = $this->recordRepo->getTypeIdByKey('bsn');

        Prevalidation::where([
            'state' => 'pending'
        ])->whereHas('prevalidation_records', function(
            Builder $builder
        ) use ($record_type_id, $bsn) {
            $builder->where([
                'record_type_id' => $record_type_id,
                'value' => $bsn,
            ]);
        })->get()->each(function(Prevalidation $prevalidation) {
            $prevalidation->assignToIdentity(auth_address());
        });

        return [
            'success' => true
        ];
    }
}
