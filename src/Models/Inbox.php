<?php

namespace MathieuBretaud\FilamentMessenger\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use MathieuBretaud\FilamentMessenger\Enums\InboxStatus;

class Inbox extends Model
{
    use SoftDeletes;

    protected $table = 'inboxes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'creator_id',
        'recipient_id',
        'status',
        'type',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InboxStatus::class,
        ];
    }

    /**
     * Accessor for the title of the inbox. If the inbox is created by
     * a user, the title will be the name of the user. If the inbox is created
     * by a system, the title should be set while creating the inbox.
     */
    protected function inboxTitle(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->title) {
                    return $this->title;
                }

                $userModel = config('auth.providers.users.model');

                if ($this->creator_id == Auth::id() && $this->recipient_id) {
                    return $this->recipient?->name ?? $userModel::find($this->recipient_id)?->name;
                }

                if ($this->creator_id) {
                    return $this->creator?->name ?? $userModel::find($this->creator_id)?->name;
                }

                return __('filament-messenger::messages.untitled');
            }
        );
    }

    /**
     * Retrieves an attribute representing all messages associated with the inbox.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\MathieuBretaud\FilamentMessenger\Models\Message>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Retrieves the latest message in the inbox.
     *
     * This method fetches the most recent message associated with the inbox by
     * ordering the messages in descending order of creation.
     * If messages are already loaded, it returns the first one from the collection.
     */
    public function latestMessage(): ?Message
    {
        if ($this->relationLoaded('messages')) {
            return $this->messages->first();
        }

        return $this->messages()->latest()->first();
    }

    /**
     * Retrieves an attribute representing all users associated with the inbox,
     * excluding the current authenticated user.
     */
    public function otherUsers(): Attribute
    {
        return Attribute::make(
            get: function () {
                $otherUserId = null;
                $otherUser = null;

                if ($this->creator_id == Auth::id()) {
                    $otherUserId = $this->recipient_id;
                    $otherUser = $this->recipient;
                } else {
                    $otherUserId = $this->creator_id;
                    $otherUser = $this->creator;
                }

                if ($otherUser) {
                    return collect([$otherUser]);
                }

                $userModel = config('auth.providers.users.model');
                return $otherUserId ? $userModel::where('id', $otherUserId)->get() : collect();
            }
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'creator_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'recipient_id');
    }

    /**
     * Determine if the current authenticated user is the creator of the inbox.
     */
    public function isCreator(): bool
    {
        return $this->creator_id === Auth::id();
    }

    /**
     * Determine if the last message in the conversation was sent by the current user.
     */
    public function isLastMessageFromCurrentUser(): bool
    {
        $latestMessage = $this->latestMessage();

        return $latestMessage && $latestMessage->user_id === Auth::id();
    }

    /**
     * Get the tab where this conversation should appear for the current user.
     */
    public function getTabForCurrentUser(): string
    {
        return match ($this->status) {
            InboxStatus::MESSAGE => $this->isCreator() ? 'sent' : 'new',
            InboxStatus::IN_PROGRESS => $this->isLastMessageFromCurrentUser()
                ? InboxStatus::IN_PROGRESS->value
                : 'new',
            InboxStatus::TREATED => InboxStatus::TREATED->value,
            default => 'new',
        };
    }
}
