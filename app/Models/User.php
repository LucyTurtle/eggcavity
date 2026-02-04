<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_DEVELOPER = 'developer';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getWishlistShareUrlAttribute(): ?string
    {
        return route('wishlists.shared', ['user' => $this->id]);
    }

    public function getWishlistShareCreaturesUrlAttribute(): ?string
    {
        return route('wishlists.shared.creatures', ['user' => $this->id]);
    }

    public function getWishlistShareItemsUrlAttribute(): ?string
    {
        return route('wishlists.shared.items', ['user' => $this->id]);
    }

    public function getWishlistShareTravelsUrlAttribute(): ?string
    {
        return route('wishlists.shared.travels', ['user' => $this->id]);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isDeveloper(): bool
    {
        return $this->role === self::ROLE_DEVELOPER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->isDeveloper();
    }

    public function isUser(): bool
    {
        return in_array($this->role, [self::ROLE_USER, self::ROLE_ADMIN, self::ROLE_DEVELOPER], true);
    }

    public function hasRole(string $role): bool
    {
        if ($role === self::ROLE_DEVELOPER) {
            return $this->isDeveloper();
        }
        if ($role === self::ROLE_ADMIN) {
            return $this->isAdmin();
        }
        return $this->role === $role;
    }

    public function creatureWishlists(): HasMany
    {
        return $this->hasMany(CreatureWishlist::class);
    }

    public function itemWishlists(): HasMany
    {
        return $this->hasMany(ItemWishlist::class);
    }

    public function travelWishlists(): HasMany
    {
        return $this->hasMany(TravelWishlist::class);
    }
}
