<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->foreignId('schedule_slot_id')
                ->nullable()
                ->after('user_id')
                ->constrained('schedule_slots')
                ->nullOnDelete();

            $table->index(['schedule_slot_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schedule_slot_id');
        });
    }
};