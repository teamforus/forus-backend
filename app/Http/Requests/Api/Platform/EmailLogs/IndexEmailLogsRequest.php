<?php

namespace App\Http\Requests\Api\Platform\EmailLogs;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Organization $organization
 */
class IndexEmailLogsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [EmailLog::class, $this->organization, $this->only([
            'fund_request_id', 'identity_id',
        ])]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'q' => $this->qRule(),
            'per_page' => $this->perPageRule(),
            'identity_id' => [
                'required_without:fund_request_id',
                function (string $attribute, mixed $value, callable $fail) {
                    $identity = Identity::firstWhere('id', (int) $value);

                    if (!$identity || !Gate::allows('showSponsorIdentities', [$this->organization, $identity])) {
                        $fail(trans('validation.in', ['attribute' => $attribute]));
                    }
                },
            ],
            'fund_request_id' => [
                'required_without:identity_id',
                function (string $attribute, mixed $value, callable $fail) {
                    $fundRequest = FundRequest::query()->find((int) $value);

                    if (!$fundRequest || !Gate::allows('viewAsValidator', [$fundRequest, $this->organization])) {
                        $fail(trans('validation.in', ['attribute' => $attribute]));
                    }
                },
            ],
        ];
    }
}
