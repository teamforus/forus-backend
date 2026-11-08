<?php

namespace App\Services\DCPService\Contracts;

use App\Services\DCPService\Models\DCPOrder;

interface DCPOrderable
{
    /**
     * Source fields which can be used in the mapping of the connection: key => label.
     *
     * @return array<string, string>
     */
    public static function dcpOrderSourceFields(): array;

    /**
     * Values of the source fields: key => value, keys as in dcpOrderSourceFields.
     * Used to build the payload when the order is created and when it is retried.
     *
     * @return array<string, string|null>
     */
    public function dcpOrderSourceValues(): array;

    /**
     * @return array<string, string|null>
     */
    public function dcpOrderDeliveryAddress(): array;

    /**
     * Reason why the order can't be sent to DCP (anymore), null when it can be sent.
     *
     * @return string|null
     */
    public function dcpOrderBlockReason(): ?string;

    /**
     * Called after the order is accepted by DCP.
     *
     * @param DCPOrder $order
     * @return void
     */
    public function onDCPOrderSent(DCPOrder $order): void;

    /**
     * @return array{name: string, voucher_id?: int|null, voucher_number?: string|null}
     */
    public function dcpOrderableDetails(): array;
}
