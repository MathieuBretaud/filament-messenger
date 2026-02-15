<?php

namespace MathieuBretaud\FilamentMessenger\Enums;

use MathieuBretaud\FilamentMessenger\Models\Inbox;

enum InboxStatus: string
{
    case MESSAGE = 'message';
    case IN_PROGRESS = 'in_progress';
    case TREATED = 'treated';

    public function label(): string
    {
        return match ($this) {
            self::MESSAGE => trans('filament-messenger::messages.status_message'),
            self::IN_PROGRESS => trans('filament-messenger::messages.status_in_progress'),
            self::TREATED => trans('filament-messenger::messages.status_treated'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MESSAGE => 'info',
            self::IN_PROGRESS => 'warning',
            self::TREATED => 'success',
        };
    }

    public function displayLabel(Inbox $inbox): string
    {
        return match ($this) {
            self::MESSAGE => $inbox->isCreator()
                ? trans('filament-messenger::messages.status_sent')
                : trans('filament-messenger::messages.status_new'),
            self::IN_PROGRESS => $inbox->isLastMessageFromCurrentUser()
                ? trans('filament-messenger::messages.status_in_progress')
                : trans('filament-messenger::messages.status_new'),
            self::TREATED => trans('filament-messenger::messages.status_treated'),
        };
    }

    public function displayColor(Inbox $inbox): string
    {
        return match ($this) {
            self::MESSAGE => $inbox->isCreator() ? 'info' : 'danger',
            self::IN_PROGRESS => $inbox->isLastMessageFromCurrentUser() ? 'warning' : 'danger',
            self::TREATED => 'success',
        };
    }
}
