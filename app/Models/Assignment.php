<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Assignment extends Model
{
    use HasFactory;

    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';

    public const STATUSES = [
        self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_DONE,
    ];

    protected $fillable = [
        'student_id',
        'course_id',
        'course_week_id',
        'title',
        'description',
        'due_date',
        'estimate_minutes',
        'status',
        'position',
        'priority',
        'assignment_type',
        'score',
        'max_score',
        'work_sample_url',
        'rubric',
        'from_paper_map',
        'evidence',
        'reflection',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'estimate_minutes' => 'integer',
            'position' => 'integer',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'from_paper_map' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseWeek(): BelongsTo
    {
        return $this->belongsTo(CourseWeek::class);
    }

    public function markDone(): void
    {
        $this->forceFill([
            'status' => self::STATUS_DONE,
            'completed_at' => $this->completed_at ?? Carbon::now(),
        ])->save();
    }
}
