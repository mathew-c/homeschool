<div class="northstar-page detail-page">
    <div class="page-backline">
        <a href="{{ route('dashboard') }}">Daily Board</a>
        @if ($this->assignment->course)
            <span>/</span>
            <a href="{{ route('courses.show', $this->assignment->course) }}">{{ $this->assignment->course->title }}</a>
        @endif
    </div>

    <section class="detail-hero">
        <div>
            <p class="section-kicker">{{ $this->assignment->course?->subject ?? 'Unassigned' }}</p>
            <h1>{{ $this->assignment->title }}</h1>
            <p>
                Assignment detail page for planning, grading, evidence, and portfolio notes.
            </p>
        </div>

        <div class="hero-actions">
            @if ($this->canMoveAssignment && $this->assignment->status !== \App\Models\Assignment::STATUS_DONE)
                <x-button color="secondary" icon="check" wire:click="markDone">Mark Done</x-button>
            @endif
            @if ($this->canSaveProgress)
                <x-button color="primary" icon="document-check" wire:click="save">Save</x-button>
            @endif
        </div>
    </section>

    @if ($notice)
        <x-alert color="primary" icon="check-circle" class="mb-4">
            {{ $notice }}
        </x-alert>
    @endif

    <section class="detail-grid">
        <form class="surface-panel detail-form" wire:submit="save">
            <div class="panel-title">
                <span>Assignment</span>
                <small>Click save after edits.</small>
            </div>

            <label>
                Title
                <input type="text" wire:model="title" @disabled(! $this->canEditAssignment) />
                @error('title')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Description
                <textarea rows="6" wire:model="description" @disabled(! $this->canEditAssignment)></textarea>
            </label>

            <div class="field-grid two">
                <label>
                    Course
                    <select wire:model="courseId" @disabled(! $this->canEditAssignment)>
                        <option value="">Unassigned</option>
                        @foreach ($this->courses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Status
                    <select wire:model="status" @disabled(! $this->canMoveAssignment && ! $this->canEditAssignment)>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="field-grid three">
                <label>
                    Type
                    <input type="text" wire:model="assignmentType" @disabled(! $this->canEditAssignment) />
                </label>
                <label>
                    Due date
                    <input type="date" wire:model="dueDate" @disabled(! $this->canEditAssignment) />
                </label>
                <label>
                    Minutes
                    <input type="number" min="10" max="360" wire:model="estimateMinutes" @disabled(! $this->canEditAssignment) />
                </label>
            </div>

            @if ($this->canSaveProgress)
                <x-button type="submit" color="primary" icon="document-check">Save assignment</x-button>
            @endif
        </form>

        <aside class="surface-panel property-panel">
            <div class="panel-title">
                <span>Properties</span>
                <small>Transcript and portfolio support.</small>
            </div>

            <div class="property-list">
                <div>
                    <span>Course</span>
                    <strong>{{ $this->assignment->course?->title ?? 'Unassigned' }}</strong>
                </div>
                <div>
                    <span>Learner</span>
                    <strong>{{ $this->assignment->student->name }}</strong>
                </div>
                <div>
                    <span>Completed</span>
                    <strong>{{ $this->assignment->completed_at?->format('M j, Y') ?? 'Not yet' }}</strong>
                </div>
            </div>
        </aside>
    </section>

    <section class="detail-grid">
        @if ($this->canViewGrade)
        <div class="surface-panel detail-form">
            <div class="panel-title">
                <span>Grading</span>
                <small>Score, rubric, and work sample.</small>
            </div>

            <div class="field-grid two">
                <label>
                    Score
                    <input type="number" min="0" step="0.5" wire:model="score" @disabled(! $this->canGradeAssignment) />
                </label>
                <label>
                    Max score
                    <input type="number" min="0" step="0.5" wire:model="maxScore" @disabled(! $this->canGradeAssignment) />
                </label>
            </div>

            <label>
                Work sample URL
                <input
                    type="text"
                    wire:model="workSampleUrl"
                    placeholder="Photo, file, repo, doc, or portfolio link"
                    @disabled(! $this->canGradeAssignment && ! $this->canSubmitEvidence)
                />
            </label>

            <label>
                Rubric
                <textarea rows="5" wire:model="rubric" @disabled(! $this->canGradeAssignment)></textarea>
            </label>
        </div>
        @endif

        <div class="surface-panel detail-form">
            <div class="panel-title">
                <span>Evidence</span>
                <small>What proves the work happened?</small>
            </div>

            <label>
                Evidence
                <textarea rows="6" wire:model="evidence" @disabled(! $this->canSubmitEvidence && ! $this->canEditAssignment)></textarea>
            </label>

            <label>
                Reflection
                <textarea rows="6" wire:model="reflection" @disabled(! $this->canSubmitReflection && ! $this->canEditAssignment)></textarea>
            </label>
        </div>
    </section>
</div>
