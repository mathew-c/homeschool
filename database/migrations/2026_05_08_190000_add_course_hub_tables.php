<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('school_year')->nullable()->after('level');
            $table->string('grade_level')->nullable()->after('school_year');
            $table->string('final_grade')->nullable()->after('status');
            $table->text('grading_notes')->nullable()->after('final_grade');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->string('assignment_type')->nullable()->after('priority');
            $table->decimal('score', 6, 2)->nullable()->after('assignment_type');
            $table->decimal('max_score', 6, 2)->nullable()->after('score');
            $table->string('work_sample_url')->nullable()->after('max_score');
            $table->text('rubric')->nullable()->after('work_sample_url');
        });

        Schema::create('course_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('resource_type')->default('Website');
            $table->string('cost')->nullable();
            $table->string('url')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'position']);
        });

        Schema::create('reading_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('author')->nullable();
            $table->date('date_started')->nullable();
            $table->date('date_finished')->nullable();
            $table->string('status')->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'status']);
        });

        Schema::create('course_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->date('logged_on');
            $table->string('log_type')->default('note');
            $table->string('title');
            $table->unsignedSmallInteger('minutes')->nullable();
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'logged_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_logs');
        Schema::dropIfExists('reading_logs');
        Schema::dropIfExists('course_resources');

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn([
                'assignment_type',
                'score',
                'max_score',
                'work_sample_url',
                'rubric',
            ]);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'school_year',
                'grade_level',
                'final_grade',
                'grading_notes',
            ]);
        });
    }
};
