<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveItemImage extends Model
{
    protected $fillable = [
        'archive_item_id',
        'url',
        'caption',
        'sort_order',
    ];

    public function archiveItem(): BelongsTo
    {
        return $this->belongsTo(ArchiveItem::class);
    }
}
