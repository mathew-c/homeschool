<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use App\Policies\AssignmentPolicy;
use App\Policies\CoursePolicy;
use App\Policies\StudentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Assignment::class, AssignmentPolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
