<?php

namespace App\Traits\Policies;

use App\Http\Responses\AuthorizationJsonResponse;
use Illuminate\Auth\Access\Response;

trait DeniesWithMeta
{
    /**
     * @param mixed $message
     * @param int $code
     * @return Response
     */
    protected function denyWithMeta(mixed $message, int $code = 403): Response
    {
        return AuthorizationJsonResponse::deny(is_array($message) ? $message : array_merge([
            'key' => $message,
            'error' => $message,
        ], trans_fb("policies/reimbursements.$message", [
            'title' => $message,
            'message' => $message,
        ])), $code);
    }
}