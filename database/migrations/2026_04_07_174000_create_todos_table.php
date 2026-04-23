<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('scheduled_for')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('role_score')->default(0);
            $table->unsignedInteger('ai_score')->nullable();
            $table->unsignedInteger('priority_score')->default(0);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'priority_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
