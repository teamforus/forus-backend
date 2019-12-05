<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;

class FormRequest extends \Illuminate\Foundation\Http\FormRequest
{
    protected $message;

    /**
     * @param string $message
     * @throws AuthorizationException
     */
    public function deny($message = 'This action is unauthorized.') {
        $this->message = $message;
        $this->failedAuthorization();
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException($this->message);
    }
}
