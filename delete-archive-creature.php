#!/usr/bin/env php
<?php
/**
 * One-off script to delete an archive creature by slug. Run from app root:
 *   php delete-archive-creature.php <slug>
 * Example:
 *   php delete-archive-creature.php trefulp
 */

$app = require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$slug = $argv[1] ?? null;
if ($slug === null || $slug === '') {
    echo "Usage: php delete-archive-creature.php <slug>\n";
    echo "Example: php delete-archive-creature.php trefulp\n";
    exit(1);
}

$item = \App\Models\ArchiveItem::where('slug', $slug)->first();
if (!$item) {
    echo "No archive creature found with slug: {$slug}\n";
    exit(1);
}

echo "About to delete: {$item->title} (slug: {$item->slug})\n";
echo "This will remove the creature, its stages, images, wishlist entries, and related data.\n";
echo "Type 'yes' to confirm: ";
$line = trim(fgets(STDIN));
if (strtolower($line) !== 'yes') {
    echo "Aborted.\n";
    exit(0);
}

// Delete related records (in case DB cascades aren't enabled)
$item->creatureWishlists()->delete();
$item->pendingTravelSuggestions()->delete();
$item->travelSuggestions()->delete();
$item->stages()->delete();
$item->images()->delete();
$item->delete();

echo "Deleted archive creature: {$slug}\n";
exit(0);
