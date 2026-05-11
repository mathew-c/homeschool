<div class="northstar-page">
    <section class="page-heading">
        <div>
            <p class="section-kicker">Daily execution</p>
            <h1>Assignments</h1>
            <p>
                Drag the day forward, capture paper mind-map nodes, and open a card when it needs details,
                grading, evidence, or portfolio notes.
            </p>
        </div>

        @if ($this->students->isNotEmpty())
            <div class="student-switcher">
                <label for="student">Learner</label>
                @if ($this->canSwitchStudents)
                    <select id="student" wire:model.live="selectedStudentId">
                        @foreach ($this->students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                        @endforeach
                    </select>
                @else
                    <strong>{{ $this->selectedStudent?->name }}</strong>
                @endif
            </div>
        @endif
    </section>

    @if ($this->students->isEmpty())
        <section class="surface-panel">
            <div class="empty-state">
                <strong>No visible learners</strong>
                <span>Your account does not have access to a student record yet.</span>
            </div>
        </section>
    @else

    @if ($notice)
        <x-alert color="primary" icon="check-circle" class="mb-4">
            {{ $notice }}
        </x-alert>
    @endif

    <section class="stats-grid">
        <article>
            <span>Assigned</span>
            <strong>{{ $this->stats['assigned'] }}</strong>
            <p>{{ $this->stats['minutes'] }} planned minutes</p>
        </article>
        <article>
            <span>In progress</span>
            <strong>{{ $this->stats['inProgress'] }}</strong>
            <p>Keep this column narrow</p>
        </article>
        <article>
            <span>Done</span>
            <strong>{{ $this->stats['done'] }}</strong>
            <p>{{ $this->stats['evidence'] }} with evidence</p>
        </article>
        <article>
            <span>Total cards</span>
            <strong>{{ $this->stats['total'] }}</strong>
            <p>Across this learner's board</p>
        </article>
    </section>

    @if ($this->canManagePlanning)
    <section class="planning-grid">
        <section class="surface-panel">
            <div class="panel-title">
                <span>Paper mind map capture</span>
                <small>One node per line</small>
            </div>

            <div class="space-y-4">
                <div class="field-grid two">
                    <label>
                        Course
                        <select wire:model="selectedCourseId">
                            @foreach ($this->courses as $course)
                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Fast add course
                        <input type="text" wire:model="newCourseTitle" placeholder="Marine biology, Spanish I..." />
                    </label>
                </div>

                <div class="field-grid three compact">
                    <label>
                        Subject
                        <select wire:model="newCourseSubject">
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject }}">{{ $subject }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Credits
                        <input type="number" min="0" max="2" step="0.25" wire:model="newCourseCredits" />
                    </label>
                    <label>
                        Hours
                        <input type="number" min="1" max="12" wire:model="newCourseWeeklyHours" />
                    </label>
                </div>

                @if ($this->canCreateCourses)
                    <x-button sm color="secondary" icon="plus" wire:click="addCourse">
                        Add course
                    </x-button>
                @endif

                <label>
                    Paper map nodes
                    <textarea
                        rows="6"
                        wire:model="paperMapText"
                        placeholder="Ratio scavenger hunt&#10;Founding arguments debate&#10;Film scene analysis..."
                    ></textarea>
                    @error('paperMapText')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </label>

                @if ($this->canCreateAssignments)
                    <x-button color="primary" icon="clipboard-document-list" wire:click="capturePaperMap">
                        Capture nodes to Assigned
                    </x-button>
                @endif
            </div>
        </section>

        <section class="surface-panel">
            <div class="panel-title">
                <span>Add assignment</span>
                <small>For a parent-planned task</small>
            </div>

            <form class="space-y-4" wire:submit="addAssignment">
                <label>
                    Assignment title
                    <input type="text" wire:model="newAssignmentTitle" placeholder="Draft one claim with evidence" />
                    @error('newAssignmentTitle')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </label>

                <label>
                    Description
                    <textarea rows="4" wire:model="newAssignmentDescription"></textarea>
                </label>

                <div class="field-grid two">
                    <label>
                        Due date
                        <input type="date" wire:model="newAssignmentDueDate" />
                    </label>
                    <label>
                        Minutes
                        <input type="number" min="10" max="360" wire:model="newAssignmentMinutes" />
                    </label>
                </div>

                <x-button type="submit" color="primary" icon="plus">
                    Add to Assigned
                </x-button>
            </form>
        </section>
    </section>
    @endif

    <section class="board">
        @foreach ($columns as $status => $column)
            <div
                class="board-column column-{{ $column['tone'] }}"
                wire:key="column-{{ $status }}"
            >
                <header>
                    <div>
                        <h2>{{ $column['label'] }}</h2>
                        <p>{{ $column['help'] }}</p>
                    </div>
                    <span>{{ $this->boardAssignments->get($status)->count() }}</span>
                </header>

                <div
                    class="card-stack"
                    @if ($this->canMoveAssignments)
                        wire:sort="sortAssignment"
                        wire:sort:group="assignments"
                        wire:sort:group-id="{{ $status }}"
                        wire:sort:config="{ animation: 240, easing: 'cubic-bezier(0.16, 1, 0.3, 1)', ghostClass: 'kanban-ghost', chosenClass: 'kanban-chosen', dragClass: 'kanban-drag' }"
                    @endif
                >
                    @forelse ($this->boardAssignments->get($status) as $assignment)
                        <article
                            class="assignment-card"
                            wire:key="assignment-{{ $assignment->id }}"
                            wire:sort:item="{{ $assignment->id }}"
                        >
                            <div class="assignment-top">
                                @if ($this->canMoveAssignments)
                                    <span class="drag-handle" aria-hidden="true">
                                        ::
                                    </span>
                                @endif
                                <div>
                                    <p class="course-label">{{ $assignment->course?->subject ?? 'Unassigned' }}</p>
                                    <a class="assignment-title-link" href="{{ route('assignments.show', $assignment) }}" wire:sort:ignore>
                                        <h3>{{ $assignment->title }}</h3>
                                    </a>
                                </div>
                            </div>

                            @if ($assignment->description)
                                <p class="assignment-description">{{ $assignment->description }}</p>
                            @endif

                            <div class="assignment-meta">
                                <span>{{ $assignment->estimate_minutes }} min</span>
                                @if ($assignment->due_date)
                                    <span>Due {{ $assignment->due_date->format('M j') }}</span>
                                @endif
                                @if ($assignment->from_paper_map)
                                    <span>Paper map</span>
                                @endif
                            </div>

                            @if ($status === \App\Models\Assignment::STATUS_DONE && $assignment->evidence)
                                <p class="assignment-description">
                                    Evidence saved. Open the card for details.
                                </p>
                            @endif

                            <footer wire:sort:ignore>
                                @if ($this->canMoveAssignments)
                                    @if ($status !== \App\Models\Assignment::STATUS_ASSIGNED)
                                        <button type="button" wire:click="moveTo({{ $assignment->id }}, 'assigned')" x-on:click.stop>Assigned</button>
                                    @endif
                                    @if ($status !== \App\Models\Assignment::STATUS_IN_PROGRESS)
                                        <button type="button" wire:click="moveTo({{ $assignment->id }}, 'in_progress')" x-on:click.stop>In Progress</button>
                                    @endif
                                    @if ($status !== \App\Models\Assignment::STATUS_DONE)
                                        <button type="button" wire:click="moveTo({{ $assignment->id }}, 'done')" x-on:click.stop>Done</button>
                                    @endif
                                @endif
                                <a href="{{ route('assignments.show', $assignment) }}" class="card-action-link">Open</a>
                                @if ($this->canDeleteAssignments)
                                    <button type="button" class="danger-link" wire:click="deleteAssignment({{ $assignment->id }})" x-on:click.stop>Remove</button>
                                @endif
                            </footer>
                        </article>
                    @empty
                        <div class="empty-column">Drop assignments here.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </section>

    <section class="planning-grid bottom-grid">
        <section class="surface-panel">
            <div class="panel-title">
                <span>Course load</span>
                <small>{{ $this->selectedStudent?->weekly_capacity_hours }} available hours/week</small>
            </div>

            <div class="course-list">
                @foreach ($this->courses as $course)
                    <article>
                        <div>
                            <a href="{{ route('courses.show', $course) }}"><strong>{{ $course->title }}</strong></a>
                            <span>{{ $course->subject }} &middot; {{ number_format((float) $course->credit_goal, 2) }} credits &middot; {{ $course->weekly_hours }} hr/wk</span>
                        </div>
                        <em>{{ $course->done_assignments_count }}/{{ $course->assignments_count }} done</em>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="surface-panel">
            <div class="panel-title">
                <span>College-bound credit map</span>
                <small>Editable targets later; useful guardrails now</small>
            </div>

            <div class="credit-list">
                @foreach ($this->creditMap as $row)
                    <div class="credit-row">
                        <div>
                            <strong>{{ $row['subject'] }}</strong>
                            <span>{{ number_format($row['planned'], 1) }} / {{ $row['target'] }} credits</span>
                        </div>
                        <div class="credit-bar">
                            <span style="width: {{ $row['percent'] }}%"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </section>
    @endif
</div>
