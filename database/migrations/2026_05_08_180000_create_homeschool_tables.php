<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('age');
            $table->string('level')->nullable();
            $table->string('target_grad_year')->nullable();
            $table->unsignedTinyInteger('weekly_capacity_hours')->default(20);
            $table->text('learning_style')->nullable();
            $table->text('strengths')->nullable();
            $table->text('friction')->nullable();
            $table->text('college_direction')->nullable();
            $table->json('interests')->nullable();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('subject');
            $table->decimal('credit_goal', 4, 2)->default(0);
            $table->unsignedTinyInteger('weekly_hours')->default(3);
            $table->string('level')->default('standard');
            $table->string('status')->default('active');
            $table->text('why')->nullable();
            $table->json('skills')->nullable();
            $table->json('outputs')->nullable();
            $table->json('resources')->nullable();
            $table->timestamps();
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('estimate_minutes')->default(45);
            $table->string('status')->default('assigned');
            $table->unsignedInteger('position')->default(0);
            $table->string('priority')->default('normal');
            $table->boolean('from_paper_map')->default(false);
            $table->text('evidence')->nullable();
            $table->text('reflection')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status', 'position']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('students');
    }
};
