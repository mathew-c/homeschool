<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Student;
use App\Support\StarterPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CourseIndex extends Component
{
    use AuthorizesRequests;

    public ?int $selectedStudentId = null;

    public function mount(StarterPlan $starterPlan): void
    {
        $this->authorize('viewAny', Course::class);

        $starterPlan->ensureFor(Auth::user());
        $this->selectedStudentId = Auth::user()->visibleStudentsQuery()->orderByDesc('age')->value('id');
    }

    public function render(): View
    {
        return view('livewire.course-index');
    }

    #[Computed]
    public function students(): EloquentCollection
    {
        return Auth::user()->visibleStudentsQuery()->orderByDesc('age')->get();
    }

    #[Computed]
    public function courses(): EloquentCollection
    {
        if (! $this->selectedStudent || ! Auth::user()->canAccessStudent($this->selectedStudent)) {
            return new EloquentCollection();
        }

        return Course::query()
            ->where('student_id', $this->selectedStudentId)
            ->withCount([
                'assignments',
                'assignments as done_assignments_count' => fn ($query) => $query->where('status', Assignment::STATUS_DONE),
                'resourceLinks',
                'readingLogs',
            ])
            ->orderByRaw("case when subject = 'English' then 1 when subject = 'Math' then 2 when subject = 'Science' then 3 when subject = 'Social Studies' then 4 else 5 end")
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function selectedStudent(): ?Student
    {
        return $this->students->firstWhere('id', $this->selectedStudentId);
    }

    #[Computed]
    public function canSwitchStudents(): bool
    {
        return Auth::user()->can('switch', Student::class);
    }
}
