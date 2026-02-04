<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveItem extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'image_url',
        'source_url',
        'published_at',
        'sort_order',
        'meta',
        'availability',
        'dates',
        'weight',
        'length',
        'obtained_from',
        'gender_profile',
        'habitat',
        'about_eggs',
        'about_creature',
        'entry_written_by',
        'design_concept_user',
        'cdwc_entry_by',
        'tags',
    ];

    protected $casts = [
        'published_at' => 'date',
        'meta' => 'array',
        'tags' => 'array',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(ArchiveItemImage::class)->orderBy('sort_order');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ArchiveStage::class)->orderBy('sort_order');
    }

    public function creatureWishlists(): HasMany
    {
        return $this->hasMany(CreatureWishlist::class, 'archive_item_id');
    }

    public function pendingAiTravelSuggestions(): HasMany
    {
        return $this->hasMany(PendingAiTravelSuggestion::class, 'archive_item_id');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->image_url ?? $this->images->first()?->url;
    }
}
