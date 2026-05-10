<div class="northstar-page">
    <section class="page-heading">
        <div>
            <p class="eyebrow">School file</p>
            <h1>Students</h1>
            <p>
                Parent-managed profile cards for bio details, photos, DOB, planning notes, and the school-file context that should follow each student across courses.
            </p>
        </div>

        <div class="hero-actions">
            <x-button icon="plus" color="primary" wire:click="openCreate">
                Add Student
            </x-button>
        </div>
    </section>

    <section
        class="student-card-grid"
        wire:sort="sortStudent"
        wire:sort:config="{ animation: 220, easing: 'cubic-bezier(0.16, 1, 0.3, 1)', ghostClass: 'student-card-ghost', chosenClass: 'student-card-chosen', dragClass: 'student-card-drag' }"
    >
        @forelse ($this->students as $student)
            <article
                class="student-profile-card"
                wire:key="student-{{ $student->id }}"
                wire:sort:item="{{ $student->id }}"
                wire:click="edit({{ $student->id }})"
            >
                <div class="student-card-photo">
                    @if ($student->photoUrl())
                        <img src="{{ $student->photoUrl() }}" alt="{{ $student->name }} class photo" />
                    @else
                        <span>{{ $student->initials() }}</span>
                    @endif

                    <button class="student-drag-handle" type="button" wire:sort:handle x-on:click.stop aria-label="Reorder {{ $student->name }}">
                        ::
                    </button>
                </div>

                <div class="student-card-body">
                    <div>
                        <p class="student-card-kicker">{{ $student->level ?: 'Learning plan' }}</p>
                        <h2>{{ $student->name }}</h2>
                    </div>

                    <div class="student-card-meta">
                        <span>{{ $student->birth_date ? 'DOB '.$student->birth_date->format('M j, Y') : 'Age '.$student->age }}</span>
                        @if ($student->target_grad_year)
                            <span>Grad {{ $student->target_grad_year }}</span>
                        @endif
                        <span>{{ $student->weekly_capacity_hours }} hrs/wk</span>
                    </div>

                    @if ($student->bio)
                        <p class="student-card-copy">{{ $student->bio }}</p>
                    @elseif ($student->college_direction)
                        <p class="student-card-copy">{{ $student->college_direction }}</p>
                    @else
                        <p class="student-card-copy muted">Click to add the school-file summary, strengths, friction points, and parent notes.</p>
                    @endif

                    @if ($student->interests)
                        <div class="student-chip-row">
                            @foreach (array_slice($student->interests, 0, 5) as $interest)
                                <span>{{ $interest }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="student-card-footer">
                        <span>{{ $student->courses_count }} courses</span>
                        <span>{{ $student->assignments_count }} assignments</span>
                        <button type="button" wire:click.stop="edit({{ $student->id }})">Edit</button>
                    </div>
                </div>
            </article>
        @empty
            <button type="button" class="student-empty-state" wire:click="openCreate">
                <span>+</span>
                <strong>Add the first student profile</strong>
                <small>Cards become the front page for each school file.</small>
            </button>
        @endforelse
    </section>

    <x-modal :title="$studentId ? 'Edit student profile' : 'Add student profile'" wire>
        <form id="student-profile-form" wire:submit="save" class="student-profile-form">
            <section class="student-form-section">
                <h3>Basics</h3>

                <div class="student-form-grid">
                    <label class="field-span-2">
                        Name *
                        <input type="text" wire:model="name" required />
                        @error('name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Birth date
                        <input type="date" wire:model="birthDate" />
                        @error('birthDate') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Age *
                        <input type="number" min="1" max="99" wire:model="age" required />
                        @error('age') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Grade / level
                        <input type="text" wire:model="level" placeholder="9th grade" />
                        @error('level') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Target graduation
                        <input type="text" wire:model="targetGradYear" placeholder="2029" />
                        @error('targetGradYear') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Weekly capacity
                        <input type="number" min="1" max="80" wire:model="weeklyCapacityHours" />
                        @error('weeklyCapacityHours') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            </section>

            <section class="student-form-section">
                <h3>Class Photo</h3>

                <div class="student-form-grid">
                    <label>
                        Upload photo
                        <input type="file" wire:model="photoUpload" accept="image/*" />
                        @error('photoUpload') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Photo URL or stored path
                        <input type="text" wire:model="photoPath" placeholder="student-photos/tor.jpg" />
                        @error('photoPath') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            </section>

            <section class="student-form-section">
                <h3>School File</h3>

                <label>
                    Bio summary
                    <textarea wire:model="bio" rows="3" placeholder="A concise student profile for the front of the school file."></textarea>
                    @error('bio') <small>{{ $message }}</small> @enderror
                </label>

                <div class="student-form-grid">
                    <label>
                        Learning style
                        <textarea wire:model="learningStyle" rows="3"></textarea>
                        @error('learningStyle') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Strengths
                        <textarea wire:model="strengths" rows="3"></textarea>
                        @error('strengths') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Friction points
                        <textarea wire:model="friction" rows="3"></textarea>
                        @error('friction') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        College direction
                        <textarea wire:model="collegeDirection" rows="3"></textarea>
                        @error('collegeDirection') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label>
                    School-file notes
                    <textarea wire:model="schoolFileNotes" rows="4" placeholder="Evaluator notes, accommodations, parent observations, or records to preserve."></textarea>
                    @error('schoolFileNotes') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    Interests
                    <input type="text" wire:model="interestsText" placeholder="AI, world history, drawing, robotics" />
                    @error('interestsText') <small>{{ $message }}</small> @enderror
                </label>
            </section>
        </form>

        <x-slot:footer>
            <x-button color="secondary" wire:click="$set('modal', false)">
                Cancel
            </x-button>

            <x-button type="submit" form="student-profile-form" color="primary" loading="save">
                Save Profile
            </x-button>
        </x-slot:footer>
    </x-modal>
</div>
