<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\EmailLogs\IndexEmailLogsRequest;
use App\Http\Resources\EmailLogResource;
use App\Models\Organization;
use App\Searches\Sponsor\EmailLogSearch;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EmailLogsController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param IndexEmailLogsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexEmailLogsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [EmailLog::class, $organization, $request->only([
            'fund_request_id', 'identity_id',
        ])]);

        $search = new EmailLogSearch($request->only([
            'q', 'fund_request_id', 'identity_id',
        ]), EmailLog::query());

        return EmailLogResource::queryCollection($search->query(), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param EmailLog $emailLog
     * @return Response
     */
    public function export(Organization $organization, EmailLog $emailLog): Response
    {
        $this->authorize('export', [$emailLog, $organization]);

        return $emailLog->toPdf()->download('email.pdf');
    }
}
