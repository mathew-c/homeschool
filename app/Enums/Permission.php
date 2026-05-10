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

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
