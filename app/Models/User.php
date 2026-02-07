<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use CanResetPassword;
    use Notifiable;
    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_DEVELOPER = 'developer';
    public const ROLE_CONTENT_MANAGER = 'content_manager';
    public const ROLE_TRAVEL_SUGGESTOR = 'travel_suggestor';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'banned_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** URL slug for shared wishlists: slug of name (e.g. "test", "jane-doe"). Name is unique so this is unique. */
    public function getWishlistShareSlugAttribute(): string
    {
        return Str::slug($this->name) ?: 'user';
    }

    public function getWishlistShareUrlAttribute(): ?string
    {
        return route('wishlists.shared', ['slug' => $this->wishlist_share_slug]);
    }

    public function getWishlistShareCreaturesUrlAttribute(): ?string
    {
        return route('wishlists.shared.creatures', ['slug' => $this->wishlist_share_slug]);
    }

    public function getWishlistShareItemsUrlAttribute(): ?string
    {
        return route('wishlists.shared.items', ['slug' => $this->wishlist_share_slug]);
    }

    public function getWishlistShareTravelsUrlAttribute(): ?string
    {
        return route('wishlists.shared.travels', ['slug' => $this->wishlist_share_slug]);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function isDeveloper(): bool
    {
        return $this->role === self::ROLE_DEVELOPER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->isDeveloper();
    }

    public function isContentManager(): bool
    {
        return $this->role === self::ROLE_CONTENT_MANAGER;
    }

    public function isTravelSuggestor(): bool
    {
        return $this->role === self::ROLE_TRAVEL_SUGGESTOR;
    }

    /** Whether this user can access any admin/content area (admin, developer, or sub-admin roles). */
    public function canAccessContentArea(): bool
    {
        return $this->isAdmin()
            || $this->isContentManager()
            || $this->isTravelSuggestor();
    }

    public function isUser(): bool
    {
        return in_array($this->role, [
            self::ROLE_USER,
            self::ROLE_ADMIN,
            self::ROLE_DEVELOPER,
            self::ROLE_CONTENT_MANAGER,
            self::ROLE_TRAVEL_SUGGESTOR,
        ], true);
    }

    /**
     * Send the password reset notification. Skip if the user is banned.
     */
    public function sendPasswordResetNotification($token): void
    {
        if ($this->isBanned()) {
            return;
        }
        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }

    public function hasRole(string $role): bool
    {
        if ($role === self::ROLE_DEVELOPER) {
            return $this->isDeveloper();
        }
        if ($role === self::ROLE_ADMIN) {
            return $this->isAdmin();
        }
        if ($role === self::ROLE_CONTENT_MANAGER) {
            return $this->isContentManager();
        }
        if ($role === self::ROLE_TRAVEL_SUGGESTOR) {
            return $this->isTravelSuggestor();
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
