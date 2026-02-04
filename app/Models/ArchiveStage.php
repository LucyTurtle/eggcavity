<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveStage extends Model
{
    protected $fillable = [
        'archive_item_id',
        'stage_number',
        'image_url',
        'requirement',
        'sort_order',
    ];

    public function archiveItem(): BelongsTo
    {
        return $this->belongsTo(ArchiveItem::class);
    }

    public function travelSuggestions(): HasMany
    {
        return $this->hasMany(TravelSuggestion::class)->orderBy('sort_order');
    }
}
