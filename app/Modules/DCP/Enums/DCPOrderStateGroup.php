<?php

namespace App\Modules\DCP\Enums;

enum DCPOrderStateGroup: string
{
    case DRAFT = 'draft';
    case IN_PROGRESS = 'in_progress';
    case NEEDS_ATTENTION = 'needs_attention';
    case COMPLETED = 'completed';

    /**
     * @param DCPOrderState $state
     * @return self
     */
    public static function forState(DCPOrderState $state): self
    {
        return match ($state) {
            DCPOrderState::DRAFT => self::DRAFT,
            DCPOrderState::APPROVED, DCPOrderState::SENDING, DCPOrderState::ACCEPTED => self::IN_PROGRESS,
            DCPOrderState::REJECTED, DCPOrderState::UNKNOWN,
            DCPOrderState::CANCELED, DCPOrderState::BLOCKED => self::NEEDS_ATTENTION,
            DCPOrderState::CLOSED => self::COMPLETED,
        };
    }

    /**
     * @return DCPOrderState[]
     */
    public function states(): array
    {
        return array_values(array_filter(
            DCPOrderState::cases(),
            fn (DCPOrderState $state) => self::forState($state) === $this,
        ));
    }
}
