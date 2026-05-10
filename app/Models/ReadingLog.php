<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'assignment_id',
        'course_week_id',
        'title',
        'author',
        'date_started',
        'date_finished',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_started' => 'date',
            'date_finished' => 'date',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function courseWeek(): BelongsTo
    {
        return $this->belongsTo(CourseWeek::class);
    }
}
