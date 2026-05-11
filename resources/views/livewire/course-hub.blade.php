<div class="northstar-page detail-page">
    <div class="page-backline">
        <a href="{{ route('courses.index') }}">Courses</a>
        <span>/</span>
        <a href="{{ route('dashboard') }}">Daily Board</a>
    </div>

    <section class="detail-hero course-hero">
        <div>
            <p class="section-kicker">{{ $this->course->subject }} / {{ $this->course->student->name }}</p>
            <h1>{{ $this->course->title }}</h1>
            <p>{{ $this->course->why }}</p>
        </div>

        <div class="hub-stats">
            <article>
                <span>Assignments</span>
                <strong>{{ $this->stats['done'] }}/{{ $this->stats['assignments'] }}</strong>
            </article>
            <article>
                <span>Weeks</span>
                <strong>{{ $this->stats['weeks'] }}</strong>
            </article>
            <article>
                <span>Resources</span>
                <strong>{{ $this->stats['resources'] }}</strong>
            </article>
            <article>
                <span>Logged</span>
                <strong>{{ $this->stats['minutes'] ?: 0 }}m</strong>
            </article>
        </div>
    </section>

    @if ($notice)
        <x-alert color="primary" icon="check-circle" class="mb-4">
            {{ $notice }}
        </x-alert>
    @endif

    @if ($this->canViewSyllabus)
    <section class="surface-panel outline-panel">
        <div class="panel-title">
            <span>Week-by-week outline</span>
            <small>Editable curriculum spine for planning sessions and transcript backup.</small>
        </div>

        <div class="outline-list">
            @forelse ($this->course->outlineWeeks as $week)
                <article class="outline-week" wire:key="course-week-{{ $week->id }}">
                    <div class="outline-week-head">
                        <div class="week-number">
                            <span>Week</span>
                            <strong>{{ $week->week_number }}</strong>
                        </div>

                        @if ($this->canManageSyllabus)
                            <label class="outline-topic">
                                Topic
                                <input
                                    type="text"
                                    value="{{ $week->title }}"
                                    wire:change="updateWeek({{ $week->id }}, 'title', $event.target.value)"
                                />
                            </label>

                            <button type="button" class="subtle-danger" wire:click="deleteWeek({{ $week->id }})">Remove</button>
                        @else
                            <div class="outline-topic">
                                <span>Topic</span>
                                <strong>{{ $week->title }}</strong>
                            </div>
                        @endif
                    </div>

                    <div class="outline-detail-grid">
                        @if ($this->canManageSyllabus)
                            <label>
                                Key concepts
                                <textarea
                                    rows="3"
                                    wire:change="updateWeek({{ $week->id }}, 'focus', $event.target.value)"
                                >{{ $week->focus }}</textarea>
                            </label>

                            <label>
                                Source map
                                <textarea
                                    rows="3"
                                    wire:change="updateWeek({{ $week->id }}, 'readings', $event.target.value)"
                                >{{ $week->readings }}</textarea>
                            </label>

                            <label>
                                Video / discussion
                                <textarea
                                    rows="3"
                                    wire:change="updateWeek({{ $week->id }}, 'videos', $event.target.value)"
                                >{{ $week->videos }}</textarea>
                            </label>

                            <label>
                                Project / output
                                <textarea
                                    rows="3"
                                    wire:change="updateWeek({{ $week->id }}, 'project', $event.target.value)"
                                >{{ $week->project }}</textarea>
                            </label>
                        @else
                            <div>
                                <span>Key concepts</span>
                                <p>{{ $week->focus ?: 'No focus notes yet.' }}</p>
                            </div>

                            <div>
                                <span>Source map</span>
                                <p>{{ $week->readings ?: 'No source notes yet.' }}</p>
                            </div>

                            <div>
                                <span>Video / discussion</span>
                                <p>{{ $week->videos ?: 'No discussion notes yet.' }}</p>
                            </div>

                            <div>
                                <span>Project / output</span>
                                <p>{{ $week->project ?: 'No output notes yet.' }}</p>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-state">
                    <strong>No weekly outline yet</strong>
                    <span>Add the first planning week below. This becomes the editable spine for readings, discussion, and output.</span>
                </div>
            @endforelse
        </div>

        @if ($this->canManageSyllabus)
            <form class="outline-add" wire:submit.prevent="addWeek" novalidate>
                <div class="outline-add-head">
                    <label>
                        Week
                        <input type="number" min="1" max="52" wire:model="newWeekNumber" />
                        @error('newWeekNumber')
                            <span class="error-text">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="outline-topic">
                        Topic
                        <input type="text" wire:model="newWeekTitle" placeholder="Classical Civilizations" />
                        @error('newWeekTitle')
                            <span class="error-text">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <div class="outline-detail-grid">
                    <label>
                        Key concepts
                        <textarea rows="3" wire:model="newWeekFocus"></textarea>
                    </label>
                    <label>
                        Source map
                        <textarea rows="3" wire:model="newWeekReadings"></textarea>
                    </label>
                    <label>
                        Video / discussion
                        <textarea rows="3" wire:model="newWeekVideos"></textarea>
                    </label>
                    <label>
                        Project / output
                        <textarea rows="3" wire:model="newWeekProject"></textarea>
                    </label>
                </div>

                <div class="outline-add-actions">
                    <button type="submit" class="primary-action" wire:target="addWeek" wire:loading.attr="disabled">
                        <span wire:target="addWeek" wire:loading.remove>Add outline week</span>
                        <span wire:target="addWeek" wire:loading>Adding...</span>
                    </button>
                </div>
            </form>
        @endif
    </section>
    @endif

    <section class="hub-grid">
        @if ($this->canViewGrades)
        <form class="surface-panel detail-form" wire:submit="saveGrading">
            <div class="panel-title">
                <span>Grading</span>
                <small>Transcript fields for this course.</small>
            </div>

            <div class="field-grid two">
                <label>
                    School year
                    <input type="text" wire:model="schoolYear" @disabled(! $this->canGradeCourse) />
                </label>
                <label>
                    Grade level
                    <input type="text" wire:model="gradeLevel" @disabled(! $this->canGradeCourse) />
                </label>
            </div>

            <div class="field-grid two">
                <label>
                    Status
                    <select wire:model="status" @disabled(! $this->canGradeCourse)>
                        <option value="planned">Planned</option>
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                        <option value="complete">Complete</option>
                    </select>
                </label>
                <label>
                    Grade
                    <input type="text" wire:model="finalGrade" placeholder="A, 94%, pass..." @disabled(! $this->canGradeCourse) />
                </label>
            </div>

            <label>
                Grading notes
                <textarea rows="5" wire:model="gradingNotes" @disabled(! $this->canGradeCourse)></textarea>
            </label>

            @if ($this->canGradeCourse)
                <x-button type="submit" color="primary" icon="document-check">Save grading</x-button>
            @endif
        </form>
        @endif

        <div class="surface-panel">
            <div class="panel-title">
                <span>Assignments</span>
                <small>Daily cards connected to this class.</small>
            </div>

            <div class="linked-list">
                @forelse ($this->course->assignments as $assignment)
                    <a href="{{ route('assignments.show', $assignment) }}">
                        <strong>{{ $assignment->title }}</strong>
                        <span>{{ str($assignment->status)->headline() }} / {{ $assignment->due_date?->format('M j') ?? 'No due date' }}</span>
                    </a>
                @empty
                    <p class="empty-note">No assignments yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="hub-grid three">
        @if ($this->canViewResources)
        <form class="surface-panel detail-form" wire:submit="addResource">
            <div class="panel-title">
                <span>References</span>
                <small>Textbooks, websites, videos, apps.</small>
            </div>

            <div class="linked-list compact">
                @foreach ($this->course->resourceLinks as $resource)
                    <a href="{{ $resource->url ?: '#' }}" @if ($resource->url) target="_blank" rel="noreferrer" @endif>
                        <strong>{{ $resource->title }}</strong>
                        <span>{{ $resource->resource_type }} / {{ $resource->cost ?? 'Free' }}</span>
                    </a>
                @endforeach
            </div>

            @if ($this->canManageResources)
                <label>
                    New resource
                    <input type="text" wire:model="newResourceTitle" placeholder="OpenStax, Crash Course, Khan Academy..." />
                </label>

                <div class="field-grid two">
                    <label>
                        Type
                        <select wire:model="newResourceType">
                            @foreach ($resourceTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Cost
                        <input type="text" wire:model="newResourceCost" />
                    </label>
                </div>

                <label>
                    URL
                    <input type="text" wire:model="newResourceUrl" />
                </label>
                <label>
                    Notes
                    <textarea rows="3" wire:model="newResourceNotes"></textarea>
                </label>

                <x-button type="submit" color="primary" icon="plus">Add resource</x-button>
            @endif
        </form>
        @endif

        @if ($this->canViewReadingLogs)
        <form class="surface-panel detail-form" wire:submit="addReading">
            <div class="panel-title">
                <span>Reading Log</span>
                <small>Books, chapters, articles, and authors.</small>
            </div>

            <div class="linked-list compact">
                @foreach ($this->course->readingLogs as $reading)
                    <div class="list-row">
                        <strong>{{ $reading->title }}</strong>
                        <span>{{ $reading->author ?? 'No author' }} / {{ str($reading->status)->headline() }}</span>
                    </div>
                @endforeach
            </div>

            @if ($this->canManageReadingLogs)
                <label>
                    Title
                    <input type="text" wire:model="newReadingTitle" />
                </label>
                <label>
                    Author
                    <input type="text" wire:model="newReadingAuthor" />
                </label>
                <div class="field-grid two">
                    <label>
                        Started
                        <input type="date" wire:model="newReadingStarted" />
                    </label>
                    <label>
                        Finished
                        <input type="date" wire:model="newReadingFinished" />
                    </label>
                </div>
                <label>
                    Status
                    <select wire:model="newReadingStatus">
                        <option value="planned">Planned</option>
                        <option value="started">Started</option>
                        <option value="finished">Finished</option>
                    </select>
                </label>

                <x-button type="submit" color="primary" icon="plus">Add reading</x-button>
            @endif
        </form>
        @endif

        @if ($this->canViewCourseLogs)
        <form class="surface-panel detail-form" wire:submit="addLog">
            <div class="panel-title">
                <span>Class Log</span>
                <small>Discussion, labs, project notes, grading events.</small>
            </div>

            <div class="linked-list compact">
                @foreach ($this->course->courseLogs as $log)
                    <div class="list-row">
                        <strong>{{ $log->logged_on->format('M j') }} - {{ $log->title }}</strong>
                        <span>{{ str($log->log_type)->headline() }} / {{ $log->minutes ? $log->minutes.'m' : 'No time' }}</span>
                    </div>
                @endforeach
            </div>

            @if ($this->canManageCourseLogs)
                <label>
                    Log title
                    <input type="text" wire:model="newLogTitle" />
                </label>
                <div class="field-grid two">
                    <label>
                        Type
                        <select wire:model="newLogType">
                            <option value="note">Note</option>
                            <option value="discussion">Discussion</option>
                            <option value="lab">Lab</option>
                            <option value="project">Project</option>
                            <option value="grading">Grading</option>
                        </select>
                    </label>
                    <label>
                        Minutes
                        <input type="number" min="1" max="600" wire:model="newLogMinutes" />
                    </label>
                </div>
                <label>
                    Notes
                    <textarea rows="5" wire:model="newLogBody"></textarea>
                </label>

                <x-button type="submit" color="primary" icon="plus">Add log</x-button>
            @endif
        </form>
        @endif
    </section>
</div>
