<?php

use App\Enums\Permission;
use App\Enums\UserRole;

return [
    'registration_enabled' => env('HOMESCHOOL_REGISTRATION_ENABLED', false),
    'default_password' => env('HOMESCHOOL_DEFAULT_PASSWORD', 'password'),
    'starter_household_name' => env('HOMESCHOOL_HOUSEHOLD_NAME', 'Cornelisen Homeschool'),

    'role_permissions' => [
        UserRole::Owner->value => Permission::values(),

        UserRole::Parent->value => [
            Permission::ManageStudents->value,
            Permission::ViewStudents->value,
            Permission::SwitchStudents->value,
            Permission::ManageEvaluatorGrants->value,
            Permission::ViewCourses->value,
            Permission::ManageCourses->value,
            Permission::ViewSyllabus->value,
            Permission::ManageSyllabus->value,
            Permission::ViewResources->value,
            Permission::ManageResources->value,
            Permission::ViewAssignments->value,
            Permission::ManageAssignments->value,
            Permission::MoveAssignments->value,
            Permission::SubmitEvidence->value,
            Permission::SubmitReflections->value,
            Permission::ViewGrades->value,
            Permission::ManageGrades->value,
            Permission::ViewReadingLogs->value,
            Permission::ManageReadingLogs->value,
            Permission::ViewCourseLogs->value,
            Permission::ManageCourseLogs->value,
            Permission::ViewEvaluatorPacket->value,
            Permission::ViewAuditLog->value,
        ],

        UserRole::Student->value => [
            Permission::ViewStudents->value,
            Permission::ViewCourses->value,
            Permission::ViewSyllabus->value,
            Permission::ViewResources->value,
            Permission::ViewAssignments->value,
            Permission::MoveAssignments->value,
            Permission::SubmitEvidence->value,
            Permission::SubmitReflections->value,
            Permission::ViewGrades->value,
            Permission::ViewReadingLogs->value,
            Permission::ViewCourseLogs->value,
        ],

        UserRole::Evaluator->value => [
            Permission::ViewStudents->value,
            Permission::ViewCourses->value,
            Permission::ViewSyllabus->value,
            Permission::ViewResources->value,
            Permission::ViewAssignments->value,
            Permission::ViewGrades->value,
            Permission::ViewReadingLogs->value,
            Permission::ViewCourseLogs->value,
            Permission::ViewEvaluatorPacket->value,
        ],
    ],

    'family_accounts' => [
        [
            'name' => 'Mathew Cornelisen',
            'email' => 'mathew.cornelisen@gmail.com',
            'role' => UserRole::Owner->value,
        ],
        [
            'name' => 'Jennifer Cornelisen',
            'email' => 'jennifer.cornelisen@gmail.com',
            'role' => UserRole::Parent->value,
        ],
        [
            'name' => 'Tor Cornelisen',
            'email' => 'tor.cornelisen@gmail.com',
            'role' => UserRole::Student->value,
            'student_name' => 'Tor',
        ],
        [
            'name' => 'Matias Cornelisen',
            'email' => 'matias.cornelisen@gmail.com',
            'role' => UserRole::Student->value,
            'student_name' => 'Maty',
        ],
    ],
];
