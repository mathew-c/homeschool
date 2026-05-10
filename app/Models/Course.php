<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'title',
        'subject',
        'credit_goal',
        'weekly_hours',
        'level',
        'school_year',
        'grade_level',
        'status',
        'final_grade',
        'grading_notes',
        'why',
        'skills',
        'outputs',
        'resources',
    ];

    protected function casts(): array
    {
        return [
            'credit_goal' => 'decimal:2',
            'weekly_hours' => 'integer',
            'skills' => 'array',
            'outputs' => 'array',
            'resources' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function syllabus(): HasOne
    {
        return $this->hasOne(CourseSyllabus::class);
    }

    public function resourceLinks(): HasMany
    {
        return $this->hasMany(CourseResource::class)->orderBy('position')->orderBy('title');
    }

    public function readingLogs(): HasMany
    {
        return $this->hasMany(ReadingLog::class)->latest('date_finished')->latest();
    }

    public function outlineWeeks(): HasMany
    {
        return $this->hasMany(CourseWeek::class)->orderBy('week_number');
    }

    public function courseLogs(): HasMany
    {
        return $this->hasMany(CourseLog::class)->latest('logged_on')->latest();
    }
}
