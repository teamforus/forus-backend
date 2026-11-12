<?php

namespace App\Modules\DCP\Events;

use App\Http\Requests\BaseFormRequest;
use App\Models\Employee;
use App\Modules\DCP\Enums\DCPEventType;
use App\Modules\DCP\Enums\DCPOrderState;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\DCP\Models\DCPOrderBulk;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class DCPLifecycleEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public readonly ?DCPOrderState $state;
    public readonly ?int $statusId;
    public readonly ?string $diagnosticId;
    public readonly ?string $identityAddress;

    /**
     * @param DCPOrder|DCPOrderBulk $model
     * @param DCPEventType $type
     * @param Employee|null $employee
     * @param int|null $previousStatusId
     * @param bool $useRequestIdentity
     */
    public function __construct(
        public readonly DCPOrder|DCPOrderBulk $model,
        public readonly DCPEventType $type,
        ?Employee $employee = null,
        public readonly ?int $previousStatusId = null,
        bool $useRequestIdentity = false,
    ) {
        $this->state = $model instanceof DCPOrder ? $model->state : null;
        $this->statusId = $model instanceof DCPOrder ? $model->status_id : null;
        $this->diagnosticId = $model instanceof DCPOrder ? $model->diagnostic_id : null;
        $this->identityAddress = $employee?->identity_address ??
            ($useRequestIdentity ? BaseFormRequest::createFrom(request())->auth_address() : null);
    }
}
