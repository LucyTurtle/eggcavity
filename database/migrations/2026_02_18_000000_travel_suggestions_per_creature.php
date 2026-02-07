<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Travel suggestions are per-creature (not per-stage). One row per (archive_item_id, item_id).
     * Trinkets still display on the correct stage via item name match on the archive page.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->migrateSqlite();
        } else {
            $this->migrateMysql();
        }
    }

    private function migrateSqlite(): void
    {
        Schema::create('travel_suggestions_new', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('archive_item_id');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['archive_item_id', 'item_id']);
            $table->index('archive_item_id');
        });

        $rows = DB::table('travel_suggestions')
            ->join('archive_stages', 'archive_stages.id', '=', 'travel_suggestions.archive_stage_id')
            ->select(
                'archive_stages.archive_item_id',
                'travel_suggestions.item_id',
                'travel_suggestions.notes',
                'travel_suggestions.sort_order',
                'travel_suggestions.created_at',
                'travel_suggestions.updated_at'
            )
            ->orderBy('travel_suggestions.id')
            ->get();

        $seen = [];
        foreach ($rows as $row) {
            $key = $row->archive_item_id . '-' . $row->item_id;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            DB::table('travel_suggestions_new')->insert([
                'archive_item_id' => $row->archive_item_id,
                'item_id' => $row->item_id,
                'notes' => $row->notes,
                'sort_order' => $row->sort_order,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('travel_suggestions');
        Schema::rename('travel_suggestions_new', 'travel_suggestions');
    }

    private function migrateMysql(): void
    {
        Schema::table('travel_suggestions', function (Blueprint $table) {
            $table->unsignedBigInteger('archive_item_id')->nullable()->after('id');
        });

        DB::statement('
            UPDATE travel_suggestions ts
            INNER JOIN archive_stages s ON s.id = ts.archive_stage_id
            SET ts.archive_item_id = s.archive_item_id
        ');

        // Dedupe: keep one row per (archive_item_id, item_id), delete the rest
        $dupes = DB::table('travel_suggestions')
            ->select('archive_item_id', 'item_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('archive_item_id', 'item_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupes as $dupe) {
            DB::table('travel_suggestions')
                ->where('archive_item_id', $dupe->archive_item_id)
                ->where('item_id', $dupe->item_id)
                ->where('id', '!=', $dupe->keep_id)
                ->delete();
        }

        DB::statement('ALTER TABLE travel_suggestions MODIFY archive_item_id BIGINT UNSIGNED NOT NULL');

        Schema::table('travel_suggestions', function (Blueprint $table) {
            $table->dropUnique(['archive_stage_id', 'item_id']);
            $table->dropForeign(['archive_stage_id']);
            $table->dropColumn('archive_stage_id');
            $table->unique(['archive_item_id', 'item_id']);
            $table->index('archive_item_id');
        });
    }

    public function down(): void
    {
        // Restoring per-stage would require expanding each row to N stages; not implemented.
        $this->throwIrreversibleMigrationException();
    }
};
