<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseWeek;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CourseHub extends Component
{
    use AuthorizesRequests;

    public int $courseId;

    public string $schoolYear = '';

    public string $gradeLevel = '';

    public string $status = 'active';

    public string $finalGrade = '';

    public string $gradingNotes = '';

    public string $newResourceTitle = '';

    public string $newResourceType = 'Website';

    public string $newResourceCost = '';

    public string $newResourceUrl = '';

    public string $newResourceNotes = '';

    public string $newReadingTitle = '';

    public string $newReadingAuthor = '';

    public string $newReadingStatus = 'planned';

    public string $newReadingStarted = '';

    public string $newReadingFinished = '';

    public string $newLogTitle = '';

    public string $newLogType = 'note';

    public string $newLogMinutes = '';

    public string $newLogBody = '';

    public ?int $newWeekNumber = 1;

    public string $newWeekTitle = '';

    public string $newWeekFocus = '';

    public string $newWeekReadings = '';

    public string $newWeekVideos = '';

    public string $newWeekProject = '';

    public ?string $notice = null;

    public array $resourceTypes = ['Website', 'Textbook', 'Video Series', 'Book', 'App', 'Reference'];

    public function mount(Course $course): void
    {
        $course->loadMissing('student');

        $this->authorize('view', $course);

        $this->courseId = $course->id;
        $this->fillGrading($course);
    }

    public function render(): View
    {
        return view('livewire.course-hub');
    }

    #[Computed]
    public function course(): Course
    {
        $course = Course::query()
            ->with([
                'student',
                'syllabus',
                'assignments' => fn ($query) => $query->orderBy('status')->orderBy('position'),
                'outlineWeeks',
                'resourceLinks',
                'readingLogs',
                'courseLogs',
            ])
            ->whereKey($this->courseId)
            ->firstOrFail();

        $this->authorize('view', $course);

        return $course;
    }

    #[Computed]
    public function stats(): array
    {
        $assignments = $this->course->assignments;

        return [
            'assignments' => $assignments->count(),
            'done' => $assignments->where('status', Assignment::STATUS_DONE)->count(),
            'resources' => $this->course->resourceLinks->count(),
            'readings' => $this->course->readingLogs->count(),
            'weeks' => $this->course->outlineWeeks->count(),
            'minutes' => $this->course->courseLogs->sum('minutes'),
        ];
    }

    public function saveGrading(): void
    {
        $this->authorize('grade', $this->course);

        $validated = $this->validate([
            'schoolYear' => ['nullable', 'string', 'max:20'],
            'gradeLevel' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['planned', 'active', 'paused', 'complete'])],
            'finalGrade' => ['nullable', 'string', 'max:20'],
            'gradingNotes' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->course->forceFill([
            'school_year' => $validated['schoolYear'] ?: null,
            'grade_level' => $validated['gradeLevel'] ?: null,
            'status' => $validated['status'],
            'final_grade' => $validated['finalGrade'] ?: null,
            'grading_notes' => $validated['gradingNotes'] ?: null,
        ])->save();

        $this->refreshCourseState();
        $this->notice = 'Course grading saved.';
    }

    public function addResource(): void
    {
        $this->authorize('manageResources', $this->course);

        $validated = $this->validate([
            'newResourceTitle' => ['required', 'string', 'max:160'],
            'newResourceType' => ['required', Rule::in($this->resourceTypes)],
            'newResourceCost' => ['nullable', 'string', 'max:60'],
            'newResourceUrl' => ['nullable', 'string', 'max:255'],
            'newResourceNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->course->resourceLinks()->create([
            'title' => $validated['newResourceTitle'],
            'resource_type' => $validated['newResourceType'],
            'cost' => $validated['newResourceCost'] ?: null,
            'url' => $validated['newResourceUrl'] ?: null,
            'notes' => $validated['newResourceNotes'] ?: null,
            'position' => $this->course->resourceLinks()->max('position') + 1,
        ]);

        $this->refreshCourseState();
        $this->reset(['newResourceTitle', 'newResourceCost', 'newResourceUrl', 'newResourceNotes']);
        $this->notice = 'Resource added.';
    }

    public function addWeek(): void
    {
        $this->authorize('manageSyllabus', $this->course);

        $this->resetErrorBag();

        $validated = $this->validate([
            'newWeekNumber' => ['required', 'integer', 'min:1', 'max:52', Rule::unique('course_weeks', 'week_number')->where('course_id', $this->courseId)],
            'newWeekTitle' => ['required', 'string', 'max:160'],
            'newWeekFocus' => ['nullable', 'string', 'max:1200'],
            'newWeekReadings' => ['nullable', 'string', 'max:1200'],
            'newWeekVideos' => ['nullable', 'string', 'max:1200'],
            'newWeekProject' => ['nullable', 'string', 'max:1200'],
        ], [
            'newWeekNumber.unique' => 'That week already exists for this course.',
            'newWeekNumber.max' => 'Keep the outline to 52 weeks or edit an existing week.',
            'newWeekTitle.required' => 'Give the week a topic before adding it.',
        ], [
            'newWeekNumber' => 'week',
            'newWeekTitle' => 'topic',
        ]);

        $this->course->outlineWeeks()->create([
            'week_number' => $validated['newWeekNumber'],
            'title' => $validated['newWeekTitle'],
            'focus' => $validated['newWeekFocus'] ?: null,
            'readings' => $validated['newWeekReadings'] ?: null,
            'videos' => $validated['newWeekVideos'] ?: null,
            'project' => $validated['newWeekProject'] ?: null,
        ]);

        $this->reset(['newWeekTitle', 'newWeekFocus', 'newWeekReadings', 'newWeekVideos', 'newWeekProject']);
        $this->newWeekNumber = $this->nextWeekNumber();
        $this->refreshCourseState();
        $this->notice = 'Outline week added.';
    }

    public function updateWeek(int $weekId, string $field, string $value): void
    {
        $this->authorize('manageSyllabus', $this->course);

        abort_unless(in_array($field, ['title', 'focus', 'readings', 'videos', 'project', 'notes'], true), 422);

        $week = CourseWeek::query()
            ->where('course_id', $this->courseId)
            ->whereKey($weekId)
            ->firstOrFail();

        $week->update([$field => filled($value) ? $value : null]);

        $this->refreshCourseState();
        $this->notice = 'Outline updated.';
    }

    public function deleteWeek(int $weekId): void
    {
        $this->authorize('manageSyllabus', $this->course);

        CourseWeek::query()
            ->where('course_id', $this->courseId)
            ->whereKey($weekId)
            ->delete();

        $this->newWeekNumber = $this->nextWeekNumber();
        $this->refreshCourseState();
        $this->notice = 'Outline week removed.';
    }

    public function addReading(): void
    {
        $this->authorize('manageReadingLogs', $this->course);

        $validated = $this->validate([
            'newReadingTitle' => ['required', 'string', 'max:160'],
            'newReadingAuthor' => ['nullable', 'string', 'max:120'],
            'newReadingStatus' => ['required', Rule::in(['planned', 'started', 'finished'])],
            'newReadingStarted' => ['nullable', 'date'],
            'newReadingFinished' => ['nullable', 'date'],
        ]);

        $this->course->readingLogs()->create([
            'title' => $validated['newReadingTitle'],
            'author' => $validated['newReadingAuthor'] ?: null,
            'status' => $validated['newReadingStatus'],
            'date_started' => $validated['newReadingStarted'] ?: null,
            'date_finished' => $validated['newReadingFinished'] ?: null,
        ]);

        $this->refreshCourseState();
        $this->reset(['newReadingTitle', 'newReadingAuthor', 'newReadingStarted', 'newReadingFinished']);
        $this->notice = 'Reading log added.';
    }

    public function addLog(): void
    {
        $this->authorize('manageCourseLogs', $this->course);

        $validated = $this->validate([
            'newLogTitle' => ['required', 'string', 'max:160'],
            'newLogType' => ['required', Rule::in(['note', 'discussion', 'lab', 'project', 'grading'])],
            'newLogMinutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'newLogBody' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->course->courseLogs()->create([
            'logged_on' => now()->toDateString(),
            'title' => $validated['newLogTitle'],
            'log_type' => $validated['newLogType'],
            'minutes' => $validated['newLogMinutes'] ?: null,
            'body' => $validated['newLogBody'] ?: null,
        ]);

        $this->refreshCourseState();
        $this->reset(['newLogTitle', 'newLogMinutes', 'newLogBody']);
        $this->notice = 'Course log added.';
    }

    private function fillGrading(Course $course): void
    {
        $this->schoolYear = (string) $course->school_year;
        $this->gradeLevel = (string) $course->grade_level;
        $this->status = $course->status;
        $this->finalGrade = (string) $course->final_grade;
        $this->gradingNotes = (string) $course->grading_notes;
        $this->newWeekNumber = ((int) $course->outlineWeeks()->max('week_number')) + 1;
    }

    private function nextWeekNumber(): int
    {
        return ((int) CourseWeek::query()
            ->where('course_id', $this->courseId)
            ->max('week_number')) + 1;
    }

    private function refreshCourseState(): void
    {
        unset($this->course, $this->stats);
    }

    #[Computed]
    public function canViewSyllabus(): bool
    {
        return Auth::user()->can('viewSyllabus', $this->course);
    }

    #[Computed]
    public function canManageSyllabus(): bool
    {
        return Auth::user()->can('manageSyllabus', $this->course);
    }

    #[Computed]
    public function canViewGrades(): bool
    {
        return Auth::user()->can('viewGrades', $this->course);
    }

    #[Computed]
    public function canGradeCourse(): bool
    {
        return Auth::user()->can('grade', $this->course);
    }

    #[Computed]
    public function canViewResources(): bool
    {
        return Auth::user()->can('viewResources', $this->course);
    }

    #[Computed]
    public function canManageResources(): bool
    {
        return Auth::user()->can('manageResources', $this->course);
    }

    #[Computed]
    public function canViewReadingLogs(): bool
    {
        return Auth::user()->can('viewReadingLogs', $this->course);
    }

    #[Computed]
    public function canManageReadingLogs(): bool
    {
        return Auth::user()->can('manageReadingLogs', $this->course);
    }

    #[Computed]
    public function canViewCourseLogs(): bool
    {
        return Auth::user()->can('viewCourseLogs', $this->course);
    }

    #[Computed]
    public function canManageCourseLogs(): bool
    {
        return Auth::user()->can('manageCourseLogs', $this->course);
    }
}
