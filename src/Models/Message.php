<?php

namespace MathieuBretaud\FilamentMessenger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use MathieuBretaud\FilamentMessenger\Enums\MediaCollectionType;
use MathieuBretaud\FilamentMessenger\Models\Traits\HasMediaConvertionRegistrations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Message extends Model implements HasMedia
{
    use HasMediaConvertionRegistrations;
    use SoftDeletes;

    protected $table = 'messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'inbox_id',
        'message',
        'user_id',
        'read_by',
        'read_at',
        'notified',
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
            'read_by' => 'array',
            'read_at' => 'array',
            'notified' => 'array',
        ];
    }

    /**
     * Register media collections for the Message model.
     *
     * This method adds a media collection for 'FILAMENT_MESSAGES' and registers
     * media conversions using the defined conversion registrations.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionType::FILAMENT_MESSAGES->value);
    }

    /**
     * Returns a morph many relationship to the media table where the
     * collection_name is equal to the 'FILAMENT_MESSAGES' enum value.
     *
     * This relationship is used to fetch the attachments for the message.
     *
     * @return MorphMany<Media>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection_name', MediaCollectionType::FILAMENT_MESSAGES);
    }

    /**
     * Get the user that sent the message.
     *
     * This relationship links the message to the user who sent it.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Get the inbox that this message belongs to.
     *
     * This relationship links the message to its parent inbox.
     *
     * @return BelongsTo<Inbox>
     */
    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }
}
