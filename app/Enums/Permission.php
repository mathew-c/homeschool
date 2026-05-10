<?php

namespace App\Enums;

enum Permission: string
{
    case ManageHousehold = 'manage_household';
    case ManageUsers = 'manage_users';
    case ManagePermissions = 'manage_permissions';
    case ManageStudents = 'manage_students';
    case ViewStudents = 'view_students';
    case SwitchStudents = 'switch_students';
    case ManageEvaluatorGrants = 'manage_evaluator_grants';

    case ViewCourses = 'view_courses';
    case ManageCourses = 'manage_courses';
    case ViewSyllabus = 'view_syllabus';
    case ManageSyllabus = 'manage_syllabus';
    case ViewResources = 'view_resources';
    case ManageResources = 'manage_resources';

    case ViewAssignments = 'view_assignments';
    case ManageAssignments = 'manage_assignments';
    case MoveAssignments = 'move_assignments';
    case SubmitEvidence = 'submit_evidence';
    case SubmitReflections = 'submit_reflections';

    case ViewGrades = 'view_grades';
    case ManageGrades = 'manage_grades';
    case ViewReadingLogs = 'view_reading_logs';
    case ManageReadingLogs = 'manage_reading_logs';
    case ViewCourseLogs = 'view_course_logs';
    case ManageCourseLogs = 'manage_course_logs';

    case ViewEvaluatorPacket = 'view_evaluator_packet';
    case ViewAuditLog = 'view_audit_log';

    public function label(): string
    {
        return match ($this) {
            self::ManageHousehold => 'Manage household',
            self::ManageUsers => 'Manage users',
            self::ManagePermissions => 'Manage permissions',
            self::ManageStudents => 'Manage students',
            self::ViewStudents => 'View students',
            self::SwitchStudents => 'Switch students',
            self::ManageEvaluatorGrants => 'Manage evaluator grants',

            self::ViewCourses => 'View courses',
            self::ManageCourses => 'Manage courses',
            self::ViewSyllabus => 'View syllabus',
            self::ManageSyllabus => 'Manage syllabus',
            self::ViewResources => 'View resources',
            self::ManageResources => 'Manage resources',

            self::ViewAssignments => 'View assignments',
            self::ManageAssignments => 'Manage assignments',
            self::MoveAssignments => 'Move assignments',
            self::SubmitEvidence => 'Submit evidence',
            self::SubmitReflections => 'Submit reflections',

            self::ViewGrades => 'View grades',
            self::ManageGrades => 'Manage grades',
            self::ViewReadingLogs => 'View reading logs',
            self::ManageReadingLogs => 'Manage reading logs',
            self::ViewCourseLogs => 'View course logs',
            self::ManageCourseLogs => 'Manage course logs',

            self::ViewEvaluatorPacket => 'View evaluator packet',
            self::ViewAuditLog => 'View audit log',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ManageHousehold,
            self::ManageUsers,
            self::ManagePermissions,
            self::ManageStudents,
            self::ViewStudents,
            self::SwitchStudents,
            self::ManageEvaluatorGrants => 'Family access',

            self::ViewCourses,
            self::ManageCourses,
            self::ViewSyllabus,
            self::ManageSyllabus,
            self::ViewResources,
            self::ManageResources => 'Courses & syllabus',

            self::ViewAssignments,
            self::ManageAssignments,
            self::MoveAssignments,
            self::SubmitEvidence,
            self::SubmitReflections => 'Assignments',

            self::ViewGrades,
            self::ManageGrades,
            self::ViewReadingLogs,
            self::ManageReadingLogs,
            self::ViewCourseLogs,
            self::ManageCourseLogs => 'Records',

            self::ViewEvaluatorPacket,
            self::ViewAuditLog => 'Audit & portfolio',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
