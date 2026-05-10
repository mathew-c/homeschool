<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('household_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->default(UserRole::Parent->value)->after('email');
            $table->json('permissions')->nullable()->after('role');
        });

        $now = now();

        DB::table('users')
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use ($now): void {
                $householdId = DB::table('households')->insertGetId([
                    'name' => "{$user->name}'s Homeschool",
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'household_id' => $householdId,
                        'role' => UserRole::Owner->value,
                    ]);
            });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('household_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('login_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->unique('login_user_id');
        });

        DB::table('students')
            ->orderBy('id')
            ->get()
            ->each(function (object $student): void {
                $householdId = DB::table('users')
                    ->where('id', $student->user_id)
                    ->value('household_id');

                DB::table('students')
                    ->where('id', $student->id)
                    ->update(['household_id' => $householdId]);
            });

        Schema::table('assignments', function (Blueprint $table) {
            $table->foreignId('course_week_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
        });

        Schema::table('reading_logs', function (Blueprint $table) {
            $table->foreignId('course_week_id')->nullable()->after('assignment_id')->constrained()->nullOnDelete();
        });

        Schema::table('course_logs', function (Blueprint $table) {
            $table->foreignId('course_week_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
        });

        Schema::create('course_syllabi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('overview')->nullable();
            $table->text('learning_goals')->nullable();
            $table->text('materials')->nullable();
            $table->text('weekly_rhythm')->nullable();
            $table->text('assessment_plan')->nullable();
            $table->text('grading_scale')->nullable();
            $table->text('evaluator_notes')->nullable();
            $table->text('transcript_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('student_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('permissions')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
            $table->index(['student_id', 'user_id']);
        });

        Schema::create('activity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['household_id', 'occurred_at']);
            $table->index(['student_id', 'occurred_at']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_events');
        Schema::dropIfExists('student_access_grants');
        Schema::dropIfExists('course_syllabi');

        Schema::table('course_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_week_id');
        });

        Schema::table('reading_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_week_id');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_week_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('login_user_id');
            $table->dropConstrainedForeignId('household_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('household_id');
            $table->dropColumn(['role', 'permissions']);
        });

        Schema::dropIfExists('households');
    }
};
