<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\ContentManagementController;
use App\Http\Controllers\TravelSuggestionController;
use App\Http\Controllers\TravelViewerController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
Route::get('/archive/{slug}', [ArchiveController::class, 'show'])->name('archive.show');
Route::get('/archive/{slug}/travels', [ArchiveController::class, 'creatureTravelViewer'])->name('archive.creature-travels');

Route::get('/items', [ItemsController::class, 'index'])->name('items.index');
Route::get('/items/{slug}', [ItemsController::class, 'show'])->name('items.show');
Route::get('/items/{slug}/on-creatures', [ItemsController::class, 'travelOnCreaturesViewer'])->name('items.travel-on-creatures');

Route::get('/travel-viewer', [TravelViewerController::class, 'index'])->name('travel-viewer.index');
Route::get('/travel-viewer/by-creature', [TravelViewerController::class, 'byCreature'])->name('travel-viewer.by-creature');
Route::get('/travel-viewer/by-travel', [TravelViewerController::class, 'byTravel'])->name('travel-viewer.by-travel');

// Auth: guest-only routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Auth: authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    // Dashboard for admin/developer (optional; users can still be logged in without access)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('role:admin,developer');
    // Manage content (admin/developer)
    Route::middleware('role:admin,developer')->prefix('dashboard/content')->name('content.')->group(function () {
        Route::get('/', [ContentManagementController::class, 'index'])->name('index');
        Route::get('creatures', [ContentManagementController::class, 'indexCreatures'])->name('creature.index');
        Route::get('creatures/create', [ContentManagementController::class, 'createCreature'])->name('creature.create');
        Route::post('creatures', [ContentManagementController::class, 'storeCreature'])->name('creature.store');
        Route::get('creatures/{archiveItem}/edit', [ContentManagementController::class, 'editCreature'])->name('creature.edit');
        Route::put('creatures/{archiveItem}', [ContentManagementController::class, 'updateCreature'])->name('creature.update');
        Route::delete('creatures/{archiveItem}', [ContentManagementController::class, 'destroyCreature'])->name('creature.destroy');
        Route::get('items', [ContentManagementController::class, 'indexItems'])->name('item.index');
        Route::get('items/create', [ContentManagementController::class, 'createItem'])->name('item.create');
        Route::post('items', [ContentManagementController::class, 'storeItem'])->name('item.store');
        Route::get('items/{item}/edit', [ContentManagementController::class, 'editItem'])->name('item.edit');
        Route::put('items/{item}', [ContentManagementController::class, 'updateItem'])->name('item.update');
        Route::delete('items/{item}', [ContentManagementController::class, 'destroyItem'])->name('item.destroy');
        Route::get('travel-suggestions', [TravelSuggestionController::class, 'index'])->name('travel-suggestions.index');
        Route::get('travel-suggestions/create', [TravelSuggestionController::class, 'create'])->name('travel-suggestions.create');
        Route::post('travel-suggestions', [TravelSuggestionController::class, 'store'])->name('travel-suggestions.store');
        Route::get('travel-suggestions/{travelSuggestion}/edit', [TravelSuggestionController::class, 'edit'])->name('travel-suggestions.edit');
        Route::put('travel-suggestions/{travelSuggestion}', [TravelSuggestionController::class, 'update'])->name('travel-suggestions.update');
        Route::delete('travel-suggestions/{travelSuggestion}', [TravelSuggestionController::class, 'destroy'])->name('travel-suggestions.destroy');
        Route::post('archive/{slug}/apply-recommended-travels', [ArchiveController::class, 'applyRecommendedToAllStages'])->name('archive.apply-recommended-travels');
    });
    // Impersonation (developer only)
    Route::post('/impersonate/{user}/start', [ImpersonationController::class, 'start'])->name('impersonate.start')->middleware('role:developer');
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    // Wishlists
    Route::get('/wishlists', [WishlistController::class, 'index'])->name('wishlists.index');
    Route::redirect('/wishlists/add', '/wishlists', 301);
    Route::get('/wishlists/add/creatures', [WishlistController::class, 'showAddCreatures'])->name('wishlists.add.creatures');
    Route::get('/wishlists/add/items', [WishlistController::class, 'showAddItems'])->name('wishlists.add.items');
    Route::get('/wishlists/add/travels', [WishlistController::class, 'showAddTravels'])->name('wishlists.add.travels');
    Route::post('/wishlist/creatures/batch', [WishlistController::class, 'storeCreatures'])->name('wishlist.creatures.store');
    Route::post('/wishlist/items/batch', [WishlistController::class, 'storeItems'])->name('wishlist.items.store');
    Route::post('/wishlist/travels/batch', [WishlistController::class, 'storeTravels'])->name('wishlist.travels.store');
    Route::post('/wishlists/share/enable', [WishlistController::class, 'shareEnable'])->name('wishlists.share.enable');
    Route::post('/wishlists/share/regenerate', [WishlistController::class, 'shareRegenerate'])->name('wishlists.share.regenerate');
    Route::post('/wishlists/share/disable', [WishlistController::class, 'shareDisable'])->name('wishlists.share.disable');
    Route::post('/wishlist/creatures', [WishlistController::class, 'storeCreature'])->name('wishlist.creature.store');
    Route::put('/wishlist/creatures/{creatureWishlist}', [WishlistController::class, 'updateCreature'])->name('wishlist.creature.update');
    Route::delete('/wishlist/creatures/{creatureWishlist}', [WishlistController::class, 'removeCreature'])->name('wishlist.creature.remove');
    Route::post('/wishlist/items', [WishlistController::class, 'storeItem'])->name('wishlist.item.store');
    Route::put('/wishlist/items/{itemWishlist}', [WishlistController::class, 'updateItem'])->name('wishlist.item.update');
    Route::delete('/wishlist/items/{itemWishlist}', [WishlistController::class, 'removeItem'])->name('wishlist.item.remove');
    Route::post('/wishlist/travels', [WishlistController::class, 'storeTravel'])->name('wishlist.travel.store');
    Route::put('/wishlist/travels/{travelWishlist}', [WishlistController::class, 'updateTravel'])->name('wishlist.travel.update');
    Route::delete('/wishlist/travels/{travelWishlist}', [WishlistController::class, 'removeTravel'])->name('wishlist.travel.remove');
});

// Public shared wishlists (by username slug; must be after auth routes so /wishlists/add/* is matched first)
Route::get('/wishlists/{slug}/creatures', [WishlistController::class, 'showSharedCreatures'])->name('wishlists.shared.creatures');
Route::get('/wishlists/{slug}/items', [WishlistController::class, 'showSharedItems'])->name('wishlists.shared.items');
Route::get('/wishlists/{slug}/travels', [WishlistController::class, 'showSharedTravels'])->name('wishlists.shared.travels');
Route::get('/wishlists/{slug}', [WishlistController::class, 'showShared'])->name('wishlists.shared');
