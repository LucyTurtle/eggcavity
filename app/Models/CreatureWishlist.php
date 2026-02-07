<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatureWishlist extends Model
{
    protected $table = 'creature_wishlists';

    protected $fillable = ['user_id', 'archive_item_id', 'amount', 'gender', 'notes', 'stage_number'];

    /** Stage number to use for display (no preference => 1). */
    public function getDisplayStageNumberAttribute(): int
    {
        return $this->stage_number !== null && $this->stage_number >= 1 ? (int) $this->stage_number : 1;
    }

    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_NON_BINARY = 'non-binary';
    public const GENDER_NO_PREFERENCE = 'no_preference';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function archiveItem(): BelongsTo
    {
        return $this->belongsTo(ArchiveItem::class, 'archive_item_id');
    }
}
