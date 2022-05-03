<?php


namespace App\Http\Responses;

use App\Exceptions\AuthorizationJsonException;
use Illuminate\Auth\Access\Response;

class AuthorizationJsonResponse extends Response
{
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toArray()['message'] ?? '';
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return is_array($this->message) ? $this->message : parent::toArray();
    }

    /**
     * Throw authorization exception if response was denied.
     *
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws AuthorizationJsonException
     */
    public function authorize(): Response
    {
        if ($this->denied()) {
            throw new AuthorizationJsonException(
                json_encode($this->message(), JSON_OBJECT_AS_ARRAY),
                $this->code()
            );
        }

        return $this;
    }
}