<?php

namespace App\Http\Requests\Api\Identity\Sessions;

use App\Http\Requests\BaseFormRequest;

/**
 * Class IndexSessionsRequest
 * @package App\Http\Requests\Api\Identity\Sessions
 */
class IndexSessionsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }
}
