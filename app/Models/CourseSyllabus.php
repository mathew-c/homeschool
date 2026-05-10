<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSyllabus extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'overview',
        'learning_goals',
        'materials',
        'weekly_rhythm',
        'assessment_plan',
        'grading_scale',
        'evaluator_notes',
        'transcript_summary',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
