<?php

namespace App\Policies;

use App\Exceptions\AuthorizationJsonException;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BasePolicy
{
    use HandlesAuthorization;

    private string $policyRoot = 'policies';

    /**
     * Name of prevalidation errors file.
     * @return string
     */
    abstract protected function getPolicyKey(): string;

    /**
     * @param mixed $message
     * @param int $code
     * @throws AuthorizationJsonException
     */
    protected function deny($message, $code = 403)
    {
        $policyError = sprintf('%s/%s.%s', $this->policyRoot, $this->getPolicyKey(), $message);

        $error = $message;
        $titleKey = sprintf('%s.title', $policyError);
        $messageKey = sprintf('%s.message', $policyError);

        $title = trans($titleKey);
        $message = trans($messageKey);

        $title = $title === $titleKey ? null : $title;
        $message = $message === $messageKey ? null : $message;

        $meta = compact('error', 'title', 'message', 'code');

        throw new AuthorizationJsonException(json_encode(array_merge_recursive(
            compact('error', 'message', 'meta')
        )), $code);
    }
}
