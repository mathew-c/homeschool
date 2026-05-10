<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Student;
use App\Support\StarterPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class HomeschoolBoard extends Component
{
    use AuthorizesRequests;

    public ?int $selectedStudentId = null;

    public ?int $selectedCourseId = null;

    public string $newAssignmentTitle = '';

    public string $newAssignmentDescription = '';

    public int $newAssignmentMinutes = 45;

    public string $newAssignmentDueDate = '';

    public string $paperMapText = '';

    public string $newCourseTitle = '';

    public string $newCourseSubject = 'English';

    public float $newCourseCredits = 0.5;

    public int $newCourseWeeklyHours = 3;

    public ?string $notice = null;

    public array $columns = [
        Assignment::STATUS_ASSIGNED => [
            'label' => 'Assigned',
            'help' => 'Ready for the day, including paper-map captures.',
            'tone' => 'slate',
        ],
        Assignment::STATUS_IN_PROGRESS => [
            'label' => 'In Progress',
            'help' => 'One active card at a time is the goal.',
            'tone' => 'sky',
        ],
        Assignment::STATUS_DONE => [
            'label' => 'Done',
            'help' => 'Evidence or reflection makes it count.',
            'tone' => 'emerald',
        ],
    ];

    public array $subjects = [
        'English',
        'Math',
        'Science',
        'Social Studies',
        'World Language',
        'Arts',
        'Computer Science',
        'Wellness',
        'Elective',
    ];

    public function mount(StarterPlan $starterPlan): void
    {
        $user = Auth::user();

        $starterPlan->ensureFor($user);

        $this->selectedStudentId = $user->visibleStudentsQuery()->orderByDesc('age')->value('id');
        $this->selectedCourseId = Course::where('student_id', $this->selectedStudentId)->oldest()->value('id');
        $this->newAssignmentDueDate = now()->toDateString();
    }

    public function render(): View
    {
        return view('livewire.homeschool-board');
    }

    #[Computed]
    public function students(): EloquentCollection
    {
        return Auth::user()
            ->visibleStudentsQuery()
            ->withCount([
                'assignments',
                'assignments as done_assignments_count' => fn ($query) => $query->where('status', Assignment::STATUS_DONE),
            ])
            ->orderByDesc('age')
            ->get();
    }

    #[Computed]
    public function selectedStudent(): ?Student
    {
        return $this->students->firstWhere('id', $this->selectedStudentId);
    }

    #[Computed]
    public function courses(): EloquentCollection
    {
        if (! $this->selectedStudentId || ! $this->selectedStudent) {
            return new EloquentCollection();
        }

        return Course::query()
            ->where('student_id', $this->selectedStudentId)
            ->withCount([
                'assignments',
                'assignments as done_assignments_count' => fn ($query) => $query->where('status', Assignment::STATUS_DONE),
            ])
            ->orderBy('subject')
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function boardAssignments(): Collection
    {
        if (! $this->selectedStudentId || ! $this->selectedStudent) {
            return collect(Assignment::STATUSES)
                ->mapWithKeys(fn (string $status): array => [$status => collect()]);
        }

        $assignments = Assignment::query()
            ->with('course')
            ->where('student_id', $this->selectedStudentId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('status');

        return collect(Assignment::STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => $assignments->get($status, collect())]);
    }

    #[Computed]
    public function stats(): array
    {
        if (! $this->selectedStudentId || ! $this->selectedStudent) {
            return [
                'total' => 0,
                'assigned' => 0,
                'inProgress' => 0,
                'done' => 0,
                'minutes' => 0,
                'evidence' => 0,
            ];
        }

        $assignments = Assignment::where('student_id', $this->selectedStudentId)->get();

        return [
            'total' => $assignments->count(),
            'assigned' => $assignments->where('status', Assignment::STATUS_ASSIGNED)->count(),
            'inProgress' => $assignments->where('status', Assignment::STATUS_IN_PROGRESS)->count(),
            'done' => $assignments->where('status', Assignment::STATUS_DONE)->count(),
            'minutes' => $assignments
                ->whereIn('status', [Assignment::STATUS_ASSIGNED, Assignment::STATUS_IN_PROGRESS])
                ->sum('estimate_minutes'),
            'evidence' => $assignments->filter(fn (Assignment $assignment): bool => filled($assignment->evidence))->count(),
        ];
    }

    #[Computed]
    public function creditMap(): Collection
    {
        $targets = collect([
            'English' => 4,
            'Math' => 4,
            'Science' => 4,
            'Social Studies' => 4,
            'World Language' => 3,
            'Arts' => 1,
            'Elective' => 4,
        ]);

        return $targets->map(function (int $target, string $subject): array {
            $courses = $this->courses->where('subject', $subject);
            $planned = (float) $courses->sum('credit_goal');

            return [
                'subject' => $subject,
                'target' => $target,
                'planned' => $planned,
                'percent' => min(100, $target > 0 ? ($planned / $target) * 100 : 0),
            ];
        })->values();
    }

    public function updatedSelectedStudentId(): void
    {
        $this->selectedStudentOrFail();

        $this->selectedCourseId = Course::where('student_id', $this->selectedStudentId)->oldest()->value('id');
        $this->notice = null;
    }

    public function addAssignment(): void
    {
        $student = $this->selectedStudentOrFail();
        $this->authorize('create', [Assignment::class, $student]);

        $validated = $this->validate([
            'newAssignmentTitle' => ['required', 'string', 'max:160'],
            'newAssignmentDescription' => ['nullable', 'string', 'max:1500'],
            'newAssignmentMinutes' => ['required', 'integer', 'min:10', 'max:360'],
            'newAssignmentDueDate' => ['nullable', 'date'],
            'selectedCourseId' => ['nullable', Rule::exists('courses', 'id')->where('student_id', $this->selectedStudentId)],
        ]);

        Assignment::create([
            'student_id' => $this->selectedStudentId,
            'course_id' => $validated['selectedCourseId'] ?? null,
            'title' => $validated['newAssignmentTitle'],
            'description' => $validated['newAssignmentDescription'] ?? null,
            'due_date' => $validated['newAssignmentDueDate'] ?: null,
            'estimate_minutes' => $validated['newAssignmentMinutes'],
            'status' => Assignment::STATUS_ASSIGNED,
            'position' => $this->nextPosition(Assignment::STATUS_ASSIGNED),
            'priority' => 'normal',
        ]);

        $this->reset(['newAssignmentTitle', 'newAssignmentDescription']);
        $this->newAssignmentMinutes = 45;
        $this->newAssignmentDueDate = now()->toDateString();
        $this->notice = 'Assignment added to Assigned.';
    }

    public function capturePaperMap(): void
    {
        $student = $this->selectedStudentOrFail();
        $this->authorize('create', [Assignment::class, $student]);

        $lines = collect(preg_split('/\R+/', $this->paperMapText) ?: [])
            ->map(fn (string $line): string => trim(preg_replace('/^[-*\x{2022}\d.)\s]+/u', '', $line) ?? ''))
            ->filter(fn (string $line): bool => mb_strlen($line) >= 3)
            ->unique()
            ->take(20)
            ->values();

        if ($lines->isEmpty()) {
            $this->addError('paperMapText', 'Add at least one usable paper mind-map node.');

            return;
        }

        $position = $this->nextPosition(Assignment::STATUS_ASSIGNED);

        $lines->each(function (string $line) use (&$position): void {
            Assignment::create([
                'student_id' => $this->selectedStudentId,
                'course_id' => $this->selectedCourseId,
                'title' => str($line)->limit(120)->toString(),
                'description' => 'Captured from paper mind map. Refine before assigning.',
                'due_date' => null,
                'estimate_minutes' => 45,
                'status' => Assignment::STATUS_ASSIGNED,
                'position' => $position++,
                'priority' => 'normal',
                'from_paper_map' => true,
            ]);
        });

        $count = $lines->count();
        $this->reset('paperMapText');
        $this->notice = "{$count} paper-map nodes captured into Assigned.";
    }

    public function addCourse(): void
    {
        $student = $this->selectedStudentOrFail();
        $this->authorize('create', [Course::class, $student]);

        $validated = $this->validate([
            'newCourseTitle' => ['required', 'string', 'max:160'],
            'newCourseSubject' => ['required', Rule::in($this->subjects)],
            'newCourseCredits' => ['required', 'numeric', 'min:0', 'max:2'],
            'newCourseWeeklyHours' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $course = Course::create([
            'student_id' => $this->selectedStudentId,
            'title' => $validated['newCourseTitle'],
            'subject' => $validated['newCourseSubject'],
            'credit_goal' => $validated['newCourseCredits'],
            'weekly_hours' => $validated['newCourseWeeklyHours'],
            'status' => 'active',
            'level' => 'standard',
            'school_year' => '2025-26',
            'grade_level' => $this->selectedStudent?->age >= 14 ? '9th' : null,
        ]);

        $course->syllabus()->create([
            'title' => "{$course->title} Syllabus",
            'overview' => 'Course goals, weekly outline, resources, assignments, logs, and grading live together here.',
        ]);

        $this->selectedCourseId = $course->id;
        $this->reset('newCourseTitle');
        $this->newCourseCredits = 0.5;
        $this->newCourseWeeklyHours = 3;
        $this->notice = 'Course added.';
    }

    public function sortAssignment(int $id, int $position, string $status): void
    {
        abort_unless(in_array($status, Assignment::STATUSES, true), 422);
        $this->selectedStudentOrFail();

        $assignment = Assignment::query()
            ->where('student_id', $this->selectedStudentId)
            ->findOrFail($id);

        $this->authorize('move', $assignment);

        $oldStatus = $assignment->status;
        $targetPosition = max(0, $position);

        $targetAssignments = Assignment::query()
            ->where('student_id', $this->selectedStudentId)
            ->where('status', $status)
            ->whereKeyNot($assignment->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->values();

        $targetAssignments->splice($targetPosition, 0, [$assignment]);

        $targetAssignments->each(function (Assignment $item, int $index) use ($assignment, $status): void {
            $updates = ['position' => $index];

            if ($item->is($assignment)) {
                $updates['status'] = $status;
                $updates['completed_at'] = $status === Assignment::STATUS_DONE
                    ? ($assignment->completed_at ?? now())
                    : null;
            }

            $item->forceFill($updates)->save();
        });

        if ($oldStatus !== $status) {
            $this->renumber($oldStatus);
        }
        $this->renumber($status);
    }

    public function moveTo(int $assignmentId, string $status): void
    {
        abort_unless(in_array($status, Assignment::STATUSES, true), 422);
        $this->selectedStudentOrFail();

        $assignment = Assignment::query()
            ->where('student_id', $this->selectedStudentId)
            ->findOrFail($assignmentId);

        $this->authorize('move', $assignment);

        $oldStatus = $assignment->status;

        $assignment->forceFill([
            'status' => $status,
            'position' => $this->nextPosition($status),
            'completed_at' => $status === Assignment::STATUS_DONE ? now() : null,
        ])->save();

        $this->renumber($oldStatus);
        $this->renumber($status);
    }

    public function saveField(int $assignmentId, string $field, string $value): void
    {
        abort_unless(in_array($field, ['evidence', 'reflection'], true), 422);
        $this->selectedStudentOrFail();

        $assignment = Assignment::query()
            ->where('student_id', $this->selectedStudentId)
            ->whereKey($assignmentId)
            ->firstOrFail();

        $this->authorize($field === 'evidence' ? 'submitEvidence' : 'submitReflection', $assignment);

        $assignment->forceFill([$field => $value])->save();

        $this->notice = $field === 'evidence' ? 'Evidence saved.' : 'Reflection saved.';
    }

    public function deleteAssignment(int $assignmentId): void
    {
        $this->selectedStudentOrFail();

        $assignment = Assignment::query()
            ->where('student_id', $this->selectedStudentId)
            ->whereKey($assignmentId)
            ->firstOrFail();

        $this->authorize('delete', $assignment);

        $assignment->delete();

        $this->notice = 'Assignment removed.';
    }

    #[Computed]
    public function canSwitchStudents(): bool
    {
        return Auth::user()->canSwitchStudents();
    }

    #[Computed]
    public function canManagePlanning(): bool
    {
        $student = $this->selectedStudent;

        if (! $student) {
            return false;
        }

        return Auth::user()->can('create', [Course::class, $student])
            || Auth::user()->can('create', [Assignment::class, $student]);
    }

    #[Computed]
    public function canCreateAssignments(): bool
    {
        return $this->selectedStudent
            ? Auth::user()->can('create', [Assignment::class, $this->selectedStudent])
            : false;
    }

    #[Computed]
    public function canCreateCourses(): bool
    {
        return $this->selectedStudent
            ? Auth::user()->can('create', [Course::class, $this->selectedStudent])
            : false;
    }

    #[Computed]
    public function canMoveAssignments(): bool
    {
        return $this->selectedStudent
            ? Auth::user()->hasPermission(\App\Enums\Permission::MoveAssignments)
                || Auth::user()->hasPermission(\App\Enums\Permission::ManageAssignments)
            : false;
    }

    #[Computed]
    public function canDeleteAssignments(): bool
    {
        return Auth::user()->hasPermission(\App\Enums\Permission::ManageAssignments);
    }

    private function nextPosition(string $status): int
    {
        return ((int) Assignment::query()
            ->where('student_id', $this->selectedStudentId)
            ->where('status', $status)
            ->max('position')) + 1;
    }

    private function renumber(string $status): void
    {
        Assignment::query()
            ->where('student_id', $this->selectedStudentId)
            ->where('status', $status)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(fn (Assignment $assignment, int $index): bool => $assignment->forceFill(['position' => $index])->save());
    }

    private function selectedStudentOrFail(): Student
    {
        $student = $this->selectedStudent;

        abort_unless($student, 403);

        return $student;
    }
}
