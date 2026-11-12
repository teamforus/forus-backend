<?php

namespace App\Modules\DCP\Contracts;

interface DCPOrderable
{
    /**
     * @return int
     */
    public function dcpOrganizationId(): int;

    /**
     * @return bool
     */
    public function dcpPrintRequested(): bool;

    /**
     * @return string
     */
    public function dcpPrintCode(): string;
}
