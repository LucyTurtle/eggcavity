<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('pending_ai_travel_suggestions', 'pending_travel_suggestions');
    }

    public function down(): void
    {
        Schema::rename('pending_travel_suggestions', 'pending_ai_travel_suggestions');
    }
};
