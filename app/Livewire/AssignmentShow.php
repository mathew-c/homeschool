<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AssignmentShow extends Component
{
    use AuthorizesRequests;

    public int $assignmentId;

    public ?int $courseId = null;

    public string $title = '';

    public string $description = '';

    public string $dueDate = '';

    public int $estimateMinutes = 45;

    public string $status = Assignment::STATUS_ASSIGNED;

    public string $assignmentType = 'Lesson';

    public string $score = '';

    public string $maxScore = '';

    public string $workSampleUrl = '';

    public string $evidence = '';

    public string $reflection = '';

    public string $rubric = '';

    public ?string $notice = null;

    public array $statusLabels = [
        Assignment::STATUS_ASSIGNED => 'Assigned',
        Assignment::STATUS_IN_PROGRESS => 'In Progress',
        Assignment::STATUS_DONE => 'Done',
    ];

    public function mount(Assignment $assignment): void
    {
        $assignment->loadMissing('student');

        $this->authorize('view', $assignment);

        $this->assignmentId = $assignment->id;
        $this->fillFromAssignment($assignment);
    }

    public function render(): View
    {
        return view('livewire.assignment-show');
    }

    #[Computed]
    public function assignment(): Assignment
    {
        $assignment = Assignment::query()
            ->with(['student', 'course.student'])
            ->whereKey($this->assignmentId)
            ->firstOrFail();

        $this->authorize('view', $assignment);

        return $assignment;
    }

    #[Computed]
    public function courses(): EloquentCollection
    {
        return Course::query()
            ->where('student_id', $this->assignment->student_id)
            ->orderBy('subject')
            ->orderBy('title')
            ->get();
    }

    public function save(): void
    {
        $assignment = $this->assignment;

        if (Auth::user()->can('update', $assignment)) {
            $this->saveFullAssignment($assignment);

            return;
        }

        $this->saveStudentProgress($assignment);
    }

    public function markDone(): void
    {
        $this->authorize('move', $this->assignment);

        $this->status = Assignment::STATUS_DONE;
        $this->save();
    }

    #[Computed]
    public function canEditAssignment(): bool
    {
        return Auth::user()->can('update', $this->assignment);
    }

    #[Computed]
    public function canMoveAssignment(): bool
    {
        return Auth::user()->can('move', $this->assignment);
    }

    #[Computed]
    public function canViewGrade(): bool
    {
        return Auth::user()->can('viewGrade', $this->assignment);
    }

    #[Computed]
    public function canGradeAssignment(): bool
    {
        return Auth::user()->can('grade', $this->assignment);
    }

    #[Computed]
    public function canSubmitEvidence(): bool
    {
        return Auth::user()->can('submitEvidence', $this->assignment);
    }

    #[Computed]
    public function canSubmitReflection(): bool
    {
        return Auth::user()->can('submitReflection', $this->assignment);
    }

    #[Computed]
    public function canSaveProgress(): bool
    {
        return $this->canEditAssignment
            || $this->canMoveAssignment
            || $this->canGradeAssignment
            || $this->canSubmitEvidence
            || $this->canSubmitReflection;
    }

    private function saveFullAssignment(Assignment $assignment): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'courseId' => ['nullable', Rule::exists('courses', 'id')->where('student_id', $assignment->student_id)],
            'dueDate' => ['nullable', 'date'],
            'estimateMinutes' => ['required', 'integer', 'min:10', 'max:360'],
            'status' => ['required', Rule::in(Assignment::STATUSES)],
            'assignmentType' => ['nullable', 'string', 'max:60'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'maxScore' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'workSampleUrl' => ['nullable', 'string', 'max:255'],
            'evidence' => ['nullable', 'string', 'max:3000'],
            'reflection' => ['nullable', 'string', 'max:3000'],
            'rubric' => ['nullable', 'string', 'max:3000'],
        ]);

        $completedAt = $validated['status'] === Assignment::STATUS_DONE
            ? ($assignment->completed_at ?? now())
            : null;

        $assignment->forceFill([
            'course_id' => $validated['courseId'] ?: null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?: null,
            'due_date' => $validated['dueDate'] ?: null,
            'estimate_minutes' => $validated['estimateMinutes'],
            'status' => $validated['status'],
            'assignment_type' => $validated['assignmentType'] ?: null,
            'score' => $validated['score'] === '' ? null : $validated['score'],
            'max_score' => $validated['maxScore'] === '' ? null : $validated['maxScore'],
            'work_sample_url' => $validated['workSampleUrl'] ?: null,
            'evidence' => $validated['evidence'] ?: null,
            'reflection' => $validated['reflection'] ?: null,
            'rubric' => $validated['rubric'] ?: null,
            'completed_at' => $completedAt,
        ])->save();

        $this->refreshAssignmentState();
        $this->notice = 'Assignment saved.';
    }

    private function saveStudentProgress(Assignment $assignment): void
    {
        $validated = $this->validate([
            'status' => ['required', Rule::in(Assignment::STATUSES)],
            'workSampleUrl' => ['nullable', 'string', 'max:255'],
            'evidence' => ['nullable', 'string', 'max:3000'],
            'reflection' => ['nullable', 'string', 'max:3000'],
        ]);

        $updates = [];

        if ($validated['status'] !== $assignment->status) {
            $this->authorize('move', $assignment);

            $updates['status'] = $validated['status'];
            $updates['completed_at'] = $validated['status'] === Assignment::STATUS_DONE
                ? ($assignment->completed_at ?? now())
                : null;
        }

        if ($validated['workSampleUrl'] !== (string) $assignment->work_sample_url
            || $validated['evidence'] !== (string) $assignment->evidence) {
            $this->authorize('submitEvidence', $assignment);

            $updates['work_sample_url'] = $validated['workSampleUrl'] ?: null;
            $updates['evidence'] = $validated['evidence'] ?: null;
        }

        if ($validated['reflection'] !== (string) $assignment->reflection) {
            $this->authorize('submitReflection', $assignment);

            $updates['reflection'] = $validated['reflection'] ?: null;
        }

        if ($updates === []) {
            $this->notice = 'No changes to save.';

            return;
        }

        $assignment->forceFill($updates)->save();
        $this->refreshAssignmentState();
        $this->notice = 'Progress saved.';
    }

    private function fillFromAssignment(Assignment $assignment): void
    {
        $this->courseId = $assignment->course_id;
        $this->title = $assignment->title;
        $this->description = (string) $assignment->description;
        $this->dueDate = $assignment->due_date?->toDateString() ?? '';
        $this->estimateMinutes = $assignment->estimate_minutes;
        $this->status = $assignment->status;
        $this->assignmentType = $assignment->assignment_type ?? 'Lesson';
        $this->score = $assignment->score === null ? '' : (string) $assignment->score;
        $this->maxScore = $assignment->max_score === null ? '' : (string) $assignment->max_score;
        $this->workSampleUrl = (string) $assignment->work_sample_url;
        $this->evidence = (string) $assignment->evidence;
        $this->reflection = (string) $assignment->reflection;
        $this->rubric = (string) $assignment->rubric;
    }

    private function refreshAssignmentState(): void
    {
        unset($this->assignment, $this->courses);
    }
}
