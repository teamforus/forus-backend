<?php

namespace App\Http\Requests\Api\Platform\Organizations\MollieConnections;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * @property-read Organization $organization
 */
abstract class BaseMollieConnectionRequest extends BaseFormRequest
{


    /**
     * @return void
     */
    abstract protected function throttle(): void;
}
