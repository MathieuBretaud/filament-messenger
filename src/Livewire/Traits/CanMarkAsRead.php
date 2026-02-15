<?php

namespace MathieuBretaud\FilamentMessenger\Livewire\Traits;

use MathieuBretaud\FilamentMessenger\Models\Message;
use Illuminate\Support\Facades\Auth;

trait CanMarkAsRead
{
    /**
     * Marks all messages in the selected conversation as read by the current user.
     *
     * Updates all unread messages in a single query using DB::raw to append
     * the current user ID to the JSON arrays for `read_by` and `read_at`.
     * This is much more efficient than iterating over each message individually.
     */
    public function markAsRead(): void
    {
        if (! $this->selectedConversation) {
            return;
        }

        $userId = Auth::id();
        $now = now()->toDateTimeString();

        // Update all messages in the conversation that haven't been read by the current user
        $this->selectedConversation->messages()
            ->whereJsonDoesntContain('read_by', $userId)
            ->update([
                'read_by' => \DB::raw("JSON_ARRAY_APPEND(read_by, '$', {$userId})"),
                'read_at' => \DB::raw("JSON_ARRAY_APPEND(read_at, '$', '{$now}')"),
            ]);
    }
}
