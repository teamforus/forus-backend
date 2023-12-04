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
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $this->throttle();
        return true;
    }

    /**
     * @return void
     */
    abstract protected function throttle(): void;
}
