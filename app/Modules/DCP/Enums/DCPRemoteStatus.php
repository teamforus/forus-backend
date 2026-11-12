<?php

namespace App\Modules\DCP\Enums;

enum DCPRemoteStatus: int
{
    case NOT_FOUND = -1;
    case API_ACCEPTED = 100;
    case BACKOFFICE_ACCEPTED = 200;
    case PROCESSING = 300;
    case PROCESSED = 400;
    case SHIPPED = 500;
    case CLOSED = 1000;
    case CLIENT_DELETED = 9000;
    case CANCELED = 9001;
    case UNPROCESSABLE = 9422;

    /**
     * @param int|null $status
     * @return DCPOrderState
     */
    public static function orderState(?int $status): DCPOrderState
    {
        return match ($status) {
            self::CLOSED->value => DCPOrderState::CLOSED,
            self::CLIENT_DELETED->value, self::CANCELED->value,
            self::UNPROCESSABLE->value => DCPOrderState::CANCELED,
            default => DCPOrderState::ACCEPTED,
        };
    }

    /**
     * @param int|null $status
     * @return string|null
     */
    public static function label(?int $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return self::tryFrom($status) ? trans('dcp::processing.statuses.' . $status) :
            trans('dcp::processing.status_unknown', ['status' => $status]);
    }
}
