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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject_key');
            $table->string('course_code')->nullable();
            $table->string('course_name')->nullable();
            $table->string('faculty_name')->nullable();
            $table->string('label');
            $table->timestamps();

            $table->unique(['user_id', 'subject_key']);
            $table->index(['user_id', 'course_code']);
        });

        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->foreignId('subject_id')
                ->nullable()
                ->after('timetable_upload_id')
                ->constrained('subjects')
                ->nullOnDelete();

            $table->index(['user_id', 'subject_id']);
        });

        $dropColumns = array_values(array_filter([
            Schema::hasColumn('schedule_slots', 'subject') ? 'subject' : null,
            Schema::hasColumn('schedule_slots', 'course_code') ? 'course_code' : null,
            Schema::hasColumn('schedule_slots', 'course_name') ? 'course_name' : null,
            Schema::hasColumn('schedule_slots', 'faculty_name') ? 'faculty_name' : null,
        ]));

        if ($dropColumns !== []) {
            Schema::table('schedule_slots', function (Blueprint $table) use ($dropColumns): void {
                $table->dropColumn($dropColumns);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('schedule_slots', 'subject')) {
            Schema::table('schedule_slots', function (Blueprint $table): void {
                $table->string('subject')->nullable()->after('ends_at');
            });
        }

        if (! Schema::hasColumn('schedule_slots', 'course_code')) {
            Schema::table('schedule_slots', function (Blueprint $table): void {
                $table->string('course_code')->nullable()->after('subject');
            });
        }

        if (! Schema::hasColumn('schedule_slots', 'course_name')) {
            Schema::table('schedule_slots', function (Blueprint $table): void {
                $table->string('course_name')->nullable()->after('course_code');
            });
        }

        if (! Schema::hasColumn('schedule_slots', 'faculty_name')) {
            Schema::table('schedule_slots', function (Blueprint $table): void {
                $table->string('faculty_name')->nullable()->after('course_name');
            });
        }

        Schema::table('schedule_slots', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('subject_id');
        });

        Schema::dropIfExists('subjects');
    }
};
