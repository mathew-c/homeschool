<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewCourses);
    }

    public function view(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ViewCourses)
            && $user->canAccessStudent($course->student);
    }

    public function create(User $user, Student $student): bool
    {
        return $user->hasPermission(Permission::ManageCourses)
            && $user->canAccessStudent($student);
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ManageCourses)
            && $user->canAccessStudent($course->student);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    public function viewSyllabus(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ViewSyllabus)
            && $user->canAccessStudent($course->student);
    }

    public function manageSyllabus(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ManageSyllabus)
            && $user->canAccessStudent($course->student);
    }

    public function viewResources(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ViewResources)
            && $user->canAccessStudent($course->student);
    }

    public function manageResources(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ManageResources)
            && $user->canAccessStudent($course->student);
    }

    public function viewReadingLogs(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ViewReadingLogs)
            && $user->canAccessStudent($course->student);
    }

    public function manageReadingLogs(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ManageReadingLogs)
            && $user->canAccessStudent($course->student);
    }

    public function viewCourseLogs(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ViewCourseLogs)
            && $user->canAccessStudent($course->student);
    }

    public function manageCourseLogs(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ManageCourseLogs)
            && $user->canAccessStudent($course->student);
    }

    public function grade(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ManageGrades)
            && $user->canAccessStudent($course->student);
    }

    public function viewGrades(User $user, Course $course): bool
    {
        return $user->hasPermission(Permission::ViewGrades)
            && $user->canAccessStudent($course->student);
    }
}
