<?php

use App\Livewire\AssignmentShow;
use App\Livewire\CourseHub;
use App\Livewire\CourseIndex;
use App\Livewire\HomeschoolBoard;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

it('sends the root url into the app flow', function () {
    get('/')->assertRedirect('/dashboard');
});

it('creates the starter plan for a new parent', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(HomeschoolBoard::class)
        ->assertOk()
        ->assertSee('Assignments');

    expect($user->students()->count())->toBe(2);
    expect($user->students()->withCount('courses')->get()->sum('courses_count'))->toBeGreaterThan(0);
    expect($user->students()->withCount('assignments')->get()->sum('assignments_count'))->toBeGreaterThan(0);
});

it('uses a three-column board and accepts sortable moves', function () {
    $user = User::factory()->create();

    actingAs($user);

    $component = Livewire::test(HomeschoolBoard::class)
        ->assertSee('Assigned')
        ->assertSee('In Progress')
        ->assertSee('Done')
        ->assertDontSee('Backlog')
        ->assertDontSee('Today')
        ->assertDontSee('Doing');

    $studentId = $user->students()->orderByDesc('age')->first()->id;
    $assignment = Assignment::where('student_id', $studentId)
        ->where('status', Assignment::STATUS_ASSIGNED)
        ->firstOrFail();

    $component
        ->call('sortAssignment', $assignment->id, 0, Assignment::STATUS_IN_PROGRESS)
        ->assertHasNoErrors();

    assertDatabaseHas('assignments', [
        'id' => $assignment->id,
        'status' => Assignment::STATUS_IN_PROGRESS,
        'position' => 0,
    ]);
});

it('renders the course index and course hub data', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(HomeschoolBoard::class);

    $course = Course::query()
        ->whereHas('student', fn ($query) => $query->where('user_id', $user->id))
        ->where('title', 'World History')
        ->firstOrFail();

    Livewire::test(CourseIndex::class)
        ->set('selectedStudentId', $course->student_id)
        ->assertOk()
        ->assertSee('Courses')
        ->assertSee('World History');

    Livewire::test(CourseHub::class, ['course' => $course])
        ->assertOk()
        ->assertSee('World History')
        ->assertSee('OpenStax World History Volume 1')
        ->assertSee('Week-by-week outline')
        ->assertSee('Early Humans and the Neolithic Revolution')
        ->assertSee('Week 1 - Early Humans and the Neolithic Revolution');
});

it('adds course hub resources readings logs and grading', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(HomeschoolBoard::class);

    $course = Course::query()
        ->whereHas('student', fn ($query) => $query->where('user_id', $user->id))
        ->where('title', 'World History')
        ->firstOrFail();

    Livewire::test(CourseHub::class, ['course' => $course])
        ->set('finalGrade', 'A')
        ->set('gradingNotes', 'Strong discussion and summary sheets.')
        ->call('saveGrading')
        ->set('newResourceTitle', 'British Museum Mesopotamia')
        ->set('newResourceType', 'Website')
        ->set('newResourceUrl', 'https://www.britishmuseum.org/')
        ->call('addResource')
        ->set('newReadingTitle', 'Hammurabi primary source notes')
        ->set('newReadingAuthor', 'World History Encyclopedia')
        ->set('newReadingStatus', 'planned')
        ->call('addReading')
        ->set('newLogTitle', 'Paper mind map discussion')
        ->set('newLogType', 'discussion')
        ->set('newLogMinutes', '25')
        ->call('addLog')
        ->set('newWeekNumber', 38)
        ->set('newWeekTitle', 'Final transcript conference')
        ->set('newWeekFocus', 'Student explains the course arc and strongest evidence.')
        ->call('addWeek')
        ->assertHasNoErrors()
        ->assertSee('Final transcript conference');

    assertDatabaseHas('courses', [
        'id' => $course->id,
        'final_grade' => 'A',
        'grading_notes' => 'Strong discussion and summary sheets.',
    ]);

    assertDatabaseHas('course_resources', [
        'course_id' => $course->id,
        'title' => 'British Museum Mesopotamia',
    ]);

    assertDatabaseHas('reading_logs', [
        'course_id' => $course->id,
        'title' => 'Hammurabi primary source notes',
    ]);

    assertDatabaseHas('course_logs', [
        'course_id' => $course->id,
        'title' => 'Paper mind map discussion',
        'minutes' => 25,
    ]);

    assertDatabaseHas('course_weeks', [
        'course_id' => $course->id,
        'week_number' => 38,
        'title' => 'Final transcript conference',
    ]);
});

it('adds the first outline week to an empty course hub', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(HomeschoolBoard::class);

    $course = Course::query()
        ->whereHas('student', fn ($query) => $query->where('user_id', $user->id))
        ->whereDoesntHave('outlineWeeks')
        ->firstOrFail();

    Livewire::test(CourseHub::class, ['course' => $course])
        ->assertSee('No weekly outline yet')
        ->set('newWeekTitle', 'Studio launch week')
        ->set('newWeekFocus', 'Choose the first project and map the key questions.')
        ->call('addWeek')
        ->assertHasNoErrors()
        ->assertSee('Studio launch week');

    assertDatabaseHas('course_weeks', [
        'course_id' => $course->id,
        'week_number' => 1,
        'title' => 'Studio launch week',
    ]);
});

it('updates course outline weeks inline', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(HomeschoolBoard::class);

    $course = Course::query()
        ->whereHas('student', fn ($query) => $query->where('user_id', $user->id))
        ->where('title', 'World History')
        ->firstOrFail();

    $week = $course->outlineWeeks()->where('week_number', 1)->firstOrFail();

    Livewire::test(CourseHub::class, ['course' => $course])
        ->call('updateWeek', $week->id, 'project', 'Updated summary sheet and oral narration.')
        ->assertHasNoErrors();

    assertDatabaseHas('course_weeks', [
        'id' => $week->id,
        'project' => 'Updated summary sheet and oral narration.',
    ]);
});

it('opens and saves assignment details', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(HomeschoolBoard::class);

    $assignment = Assignment::query()
        ->whereHas('student', fn ($query) => $query->where('user_id', $user->id))
        ->where('title', 'Unit Test: Proportional Relationships')
        ->firstOrFail();

    Livewire::test(AssignmentShow::class, ['assignment' => $assignment])
        ->assertOk()
        ->assertSee('Unit Test: Proportional Relationships')
        ->set('score', '92')
        ->set('maxScore', '100')
        ->set('evidence', 'Corrected test and notebook page.')
        ->call('markDone')
        ->assertHasNoErrors();

    assertDatabaseHas('assignments', [
        'id' => $assignment->id,
        'status' => Assignment::STATUS_DONE,
        'score' => '92.00',
        'max_score' => '100.00',
        'evidence' => 'Corrected test and notebook page.',
    ]);
});

it('captures paper map lines into assigned cards', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(HomeschoolBoard::class)
        ->set('paperMapText', "Weather station data\nArgument map")
        ->call('capturePaperMap')
        ->assertHasNoErrors()
        ->assertSee('paper-map nodes captured');

    assertDatabaseHas('assignments', [
        'title' => 'Weather station data',
        'status' => Assignment::STATUS_ASSIGNED,
        'from_paper_map' => true,
    ]);

    assertDatabaseHas('assignments', [
        'title' => 'Argument map',
        'status' => Assignment::STATUS_ASSIGNED,
        'from_paper_map' => true,
    ]);
});

it('moves assignments across the board and records evidence', function () {
    $user = User::factory()->create();

    actingAs($user);

    $component = Livewire::test(HomeschoolBoard::class);
    $studentId = $user->students()->orderByDesc('age')->first()->id;
    $assignment = Assignment::where('student_id', $studentId)
        ->where('status', Assignment::STATUS_ASSIGNED)
        ->firstOrFail();

    $component
        ->call('moveTo', $assignment->id, Assignment::STATUS_DONE)
        ->call('saveField', $assignment->id, 'evidence', 'Notebook page 4 checked')
        ->assertHasNoErrors();

    assertDatabaseHas('assignments', [
        'id' => $assignment->id,
        'status' => Assignment::STATUS_DONE,
        'evidence' => 'Notebook page 4 checked',
    ]);
});

it('deletes only assignments owned by the selected student', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(HomeschoolBoard::class);

    $students = $user->students()->orderBy('id')->get();
    $selected = $students->first();
    $other = $students->last();
    $selectedAssignment = Assignment::where('student_id', $selected->id)->firstOrFail();
    $otherAssignment = Assignment::where('student_id', $other->id)->firstOrFail();

    Livewire::test(HomeschoolBoard::class)
        ->set('selectedStudentId', $selected->id)
        ->call('deleteAssignment', $selectedAssignment->id);

    assertDatabaseMissing('assignments', ['id' => $selectedAssignment->id]);
    assertDatabaseHas('assignments', ['id' => $otherAssignment->id]);
});
