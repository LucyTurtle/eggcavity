<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelSuggestion extends Model
{
    protected $fillable = [
        'archive_item_id',
        'item_id',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function archiveItem(): BelongsTo
    {
        return $this->belongsTo(ArchiveItem::class, 'archive_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
