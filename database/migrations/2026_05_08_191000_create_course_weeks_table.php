<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('course_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number');
            $table->string('title');
            $table->text('focus')->nullable();
            $table->text('readings')->nullable();
            $table->text('videos')->nullable();
            $table->text('project')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'week_number']);
            $table->index(['course_id', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_weeks');
    }
};
