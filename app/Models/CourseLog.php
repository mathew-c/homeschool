<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'course_week_id',
        'logged_on',
        'log_type',
        'title',
        'minutes',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'logged_on' => 'date',
            'minutes' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseWeek(): BelongsTo
    {
        return $this->belongsTo(CourseWeek::class);
    }
}
