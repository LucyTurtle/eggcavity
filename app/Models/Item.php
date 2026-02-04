<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_url',
        'source_url',
        'rarity',
        'use',
        'associated_shop',
        'restock_price',
        'is_retired',
        'is_cavecash',
        'first_appeared',
        'sort_order',
    ];

    protected $casts = [
        'first_appeared' => 'date',
        'is_retired' => 'boolean',
        'is_cavecash' => 'boolean',
    ];

    public function getRarityColorAttribute(): string
    {
        if (!$this->rarity) {
            return '#000000';
        }

        $rarityLower = strtolower($this->rarity);
        
        if (str_contains($rarityLower, 'unobtainable')) {
            return '#808080'; // gray
        } elseif (str_contains($rarityLower, 'common')) {
            return 'rgb(153, 187, 255)';
        } elseif (str_contains($rarityLower, 'uncommon')) {
            return '#008000'; // green
        } elseif (str_contains($rarityLower, 'very rare')) {
            return '#FF0000'; // red
        } elseif (str_contains($rarityLower, 'virtually unobtainable')) {
            return 'rgb(198, 0, 255)';
        } elseif (str_contains($rarityLower, 'rare')) {
            return '#4169E1'; // royal blue (for regular "rare")
        }
        
        // Default to green if no match (for backwards compatibility)
        return '#008000';
    }

    public function isTravel(): bool
    {
        return strtolower((string) $this->use) === 'travel';
    }

    public function itemWishlists(): HasMany
    {
        return $this->hasMany(ItemWishlist::class);
    }

    public function travelWishlists(): HasMany
    {
        return $this->hasMany(TravelWishlist::class);
    }

    public function travelSuggestions(): HasMany
    {
        return $this->hasMany(TravelSuggestion::class);
    }
}
