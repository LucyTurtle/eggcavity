<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archive_items', function (Blueprint $table) {
            $table->string('availability')->nullable()->after('description');
            $table->string('dates')->nullable()->after('availability');
            $table->string('weight')->nullable()->after('dates');
            $table->string('length')->nullable()->after('weight');
            $table->string('obtained_from')->nullable()->after('length');
            $table->string('gender_profile')->nullable()->after('obtained_from');
            $table->string('habitat')->nullable()->after('gender_profile');
            $table->string('population_rank')->nullable()->after('habitat');
            $table->text('about_eggs')->nullable()->after('population_rank');
            $table->text('about_creature')->nullable()->after('about_eggs');
            $table->string('entry_written_by')->nullable()->after('about_creature');
            $table->json('tags')->nullable()->after('entry_written_by');
        });

        Schema::create('archive_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_number');
            $table->string('image_url');
            $table->string('requirement')->nullable(); // e.g. "250 Views", "500 Views"
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_stages');

        Schema::table('archive_items', function (Blueprint $table) {
            $table->dropColumn([
                'availability', 'dates', 'weight', 'length', 'obtained_from',
                'gender_profile', 'habitat', 'population_rank', 'about_eggs',
                'about_creature', 'entry_written_by', 'tags',
            ]);
        });
    }
};
