<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('age');
            $table->string('photo_path')->nullable()->after('target_grad_year');
            $table->text('bio')->nullable()->after('photo_path');
            $table->text('school_file_notes')->nullable()->after('college_direction');
            $table->unsignedInteger('position')->default(0)->after('school_file_notes');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date',
                'photo_path',
                'bio',
                'school_file_notes',
                'position',
            ]);
        });
    }
};
