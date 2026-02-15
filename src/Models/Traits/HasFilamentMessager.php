<?php

namespace MathieuBretaud\FilamentMessenger\Models\Traits;

use MathieuBretaud\FilamentMessenger\Models\Inbox;
use Illuminate\Database\Eloquent\Builder;

trait HasFilamentMessager
{
    /**
     * Retrieves all conversations for the current user.
     */
    public function allConversations(): Builder
    {
        return Inbox::query()
            ->where(function ($query) {
                $query->where('creator_id', $this->id)
                    ->orWhere('recipient_id', $this->id);
//
//                if ($this->type === UserType::BACK_OFFICE) {
//                    $query->orWhereNull('recipient_id');
//                }
            })
            ->orderBy('updated_at', 'desc');
    }
}
