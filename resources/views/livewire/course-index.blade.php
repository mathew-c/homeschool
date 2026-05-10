<div class="northstar-page">
    <section class="page-heading">
        <div>
            <p class="section-kicker">Curriculum backbone</p>
            <h1>Courses</h1>
            <p>One row per class, with each hub holding logs, reading, references, assignments, and grading.</p>
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
        <section class="surface-panel empty-state">
            <strong>No learner records are visible to this account.</strong>
            <span>Ask an owner or parent to connect this login to a student record or evaluator packet.</span>
        </section>
    @else
    <section class="table-surface">
        <div class="table-toolbar">
            <span class="view-pill">Default view</span>
            <span>{{ $this->selectedStudent?->level }}</span>
        </div>

        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Course Name</th>
                        <th>Assignments</th>
                        <th>Credits</th>
                        <th>Grade</th>
                        <th>Grade Level</th>
                        <th>Reading Log</th>
                        <th>Resources</th>
                        <th>School Year</th>
                        <th>Status</th>
                        <th>Subject Area</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->courses as $course)
                        <tr>
                            <td>
                                <a class="table-title-link" href="{{ route('courses.show', $course) }}">{{ $course->title }}</a>
                            </td>
                            <td>{{ $course->done_assignments_count }}/{{ $course->assignments_count }}</td>
                            <td>{{ number_format((float) $course->credit_goal, 2) }}</td>
                            <td>{{ $course->final_grade ?? '-' }}</td>
                            <td><span class="status-chip neutral">{{ $course->grade_level ?? '-' }}</span></td>
                            <td>{{ $course->reading_logs_count }}</td>
                            <td>{{ $course->resource_links_count }}</td>
                            <td>{{ $course->school_year ?? '-' }}</td>
                            <td><span class="status-chip blue">{{ str($course->status)->headline() }}</span></td>
                            <td><span class="status-chip green">{{ $course->subject }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="course-card-list">
            @foreach ($this->courses as $course)
                <a class="course-list-card" href="{{ route('courses.show', $course) }}">
                    <div>
                        <strong>{{ $course->title }}</strong>
                        <span>{{ $course->subject }} / {{ $course->grade_level ?? '-' }} grade</span>
                    </div>

                    <dl>
                        <div>
                            <dt>Assignments</dt>
                            <dd>{{ $course->done_assignments_count }}/{{ $course->assignments_count }}</dd>
                        </div>
                        <div>
                            <dt>Credits</dt>
                            <dd>{{ number_format((float) $course->credit_goal, 2) }}</dd>
                        </div>
                        <div>
                            <dt>Resources</dt>
                            <dd>{{ $course->resource_links_count }}</dd>
                        </div>
                    </dl>

                    <span class="status-chip green">{{ str($course->status)->headline() }}</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif
</div>
