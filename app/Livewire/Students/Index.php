<?php

namespace App\Livewire\Students;

use App\Livewire\Traits\Alert;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use Alert;
    use AuthorizesRequests;
    use WithFileUploads;

    public ?int $studentId = null;

    public string $name = '';

    public string $birthDate = '';

    public int $age = 12;

    public string $level = '';

    public string $targetGradYear = '';

    public int $weeklyCapacityHours = 20;

    public string $photoPath = '';

    public $photoUpload = null;

    public string $bio = '';

    public string $learningStyle = '';

    public string $strengths = '';

    public string $friction = '';

    public string $collegeDirection = '';

    public string $schoolFileNotes = '';

    public string $interestsText = '';

    public bool $modal = false;

    public function mount(): void
    {
        $this->authorize('create', Student::class);
    }

    public function render(): View
    {
        return view('livewire.students.index');
    }

    #[Computed]
    public function students(): EloquentCollection
    {
        return Auth::user()
            ->visibleStudentsQuery()
            ->with(['loginUser'])
            ->withCount(['courses', 'assignments'])
            ->orderBy('position')
            ->orderByDesc('age')
            ->orderBy('name')
            ->get();
    }

    public function openCreate(): void
    {
        $this->authorize('create', Student::class);

        $this->resetForm();
        $this->modal = true;
    }

    public function edit(Student $student): void
    {
        $this->authorize('update', $student);

        $this->studentId = $student->id;
        $this->name = $student->name;
        $this->birthDate = $student->birth_date?->toDateString() ?? '';
        $this->age = $student->age;
        $this->level = $student->level ?? '';
        $this->targetGradYear = $student->target_grad_year ?? '';
        $this->weeklyCapacityHours = $student->weekly_capacity_hours;
        $this->photoPath = $student->photo_path ?? '';
        $this->bio = $student->bio ?? '';
        $this->learningStyle = $student->learning_style ?? '';
        $this->strengths = $student->strengths ?? '';
        $this->friction = $student->friction ?? '';
        $this->collegeDirection = $student->college_direction ?? '';
        $this->schoolFileNotes = $student->school_file_notes ?? '';
        $this->interestsText = collect($student->interests ?? [])->implode(', ');
        $this->photoUpload = null;
        $this->modal = true;
    }

    public function save(): void
    {
        $student = $this->studentId
            ? Auth::user()->visibleStudentsQuery()->findOrFail($this->studentId)
            : new Student();

        $this->authorize($student->exists ? 'update' : 'create', $student->exists ? $student : Student::class);

        $validated = $this->validate();
        $photoPath = trim($validated['photoPath']) ?: null;

        if ($this->photoUpload !== null) {
            $photoPath = $this->photoUpload->store('student-photos', 'public');
        }

        $student->forceFill([
            'household_id' => Auth::user()->household_id,
            'user_id' => $student->user_id ?: Auth::id(),
            'name' => trim($validated['name']),
            'birth_date' => $validated['birthDate'] ?: null,
            'age' => $this->calculatedAge($validated['birthDate'], (int) $validated['age']),
            'level' => trim($validated['level']) ?: null,
            'target_grad_year' => trim($validated['targetGradYear']) ?: null,
            'weekly_capacity_hours' => (int) $validated['weeklyCapacityHours'],
            'photo_path' => $photoPath,
            'bio' => trim($validated['bio']) ?: null,
            'learning_style' => trim($validated['learningStyle']) ?: null,
            'strengths' => trim($validated['strengths']) ?: null,
            'friction' => trim($validated['friction']) ?: null,
            'college_direction' => trim($validated['collegeDirection']) ?: null,
            'school_file_notes' => trim($validated['schoolFileNotes']) ?: null,
            'interests' => $this->splitInterests($validated['interestsText']),
            'position' => $student->exists ? $student->position : $this->nextPosition(),
        ])->save();

        $this->resetForm();
        $this->modal = false;
        $this->success();
    }

    public function sortStudent(int $id, int $position): void
    {
        $student = Auth::user()->visibleStudentsQuery()->findOrFail($id);

        $this->authorize('update', $student);

        $students = Auth::user()
            ->visibleStudentsQuery()
            ->whereKeyNot($student->id)
            ->orderBy('position')
            ->orderByDesc('age')
            ->orderBy('name')
            ->get()
            ->values();

        $students->splice(max(0, $position), 0, [$student]);

        $students->each(fn (Student $item, int $index) => $item->forceFill(['position' => $index])->save());
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'birthDate' => ['nullable', 'date', 'before_or_equal:today'],
            'age' => ['required', 'integer', 'min:1', 'max:99'],
            'level' => ['nullable', 'string', 'max:255'],
            'targetGradYear' => ['nullable', 'string', 'max:20'],
            'weeklyCapacityHours' => ['required', 'integer', 'min:1', 'max:80'],
            'photoPath' => ['nullable', 'string', 'max:2048'],
            'photoUpload' => ['nullable', 'image', 'max:5120'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'learningStyle' => ['nullable', 'string', 'max:5000'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'friction' => ['nullable', 'string', 'max:5000'],
            'collegeDirection' => ['nullable', 'string', 'max:5000'],
            'schoolFileNotes' => ['nullable', 'string', 'max:5000'],
            'interestsText' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'birthDate' => 'birth date',
            'targetGradYear' => 'target graduation year',
            'weeklyCapacityHours' => 'weekly capacity hours',
            'photoPath' => 'photo path',
            'photoUpload' => 'photo',
            'learningStyle' => 'learning style',
            'collegeDirection' => 'college direction',
            'schoolFileNotes' => 'school file notes',
            'interestsText' => 'interests',
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'studentId',
            'name',
            'birthDate',
            'level',
            'targetGradYear',
            'photoPath',
            'photoUpload',
            'bio',
            'learningStyle',
            'strengths',
            'friction',
            'collegeDirection',
            'schoolFileNotes',
            'interestsText',
        ]);

        $this->age = 12;
        $this->weeklyCapacityHours = 20;
    }

    private function calculatedAge(?string $birthDate, int $fallback): int
    {
        if (! $birthDate) {
            return $fallback;
        }

        return max(1, Carbon::parse($birthDate)->age);
    }

    private function splitInterests(?string $text): array
    {
        if (! $text) {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $text) ?: [])
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->values()
            ->all();
    }

    private function nextPosition(): int
    {
        return (int) Auth::user()
            ->visibleStudentsQuery()
            ->max('position') + 1;
    }
}
