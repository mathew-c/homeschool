<?php

use App\Livewire\AssignmentShow;
use App\Livewire\CourseHub;
use App\Livewire\CourseIndex;
use App\Livewire\HomeschoolBoard;
use App\Models\StudentAccessGrant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create();

    actingAs($this->owner);

    Livewire::test(HomeschoolBoard::class);

    $this->tor = $this->owner->household->students()->where('name', 'Tor')->firstOrFail();
    $this->maty = $this->owner->household->students()->where('name', 'Maty')->firstOrFail();

    $this->torUser = User::factory()->student()->inHousehold($this->owner->household)->create([
        'name' => 'Tor Cornelisen',
        'email' => 'tor.cornelisen@gmail.com',
    ]);

    $this->matyUser = User::factory()->student()->inHousehold($this->owner->household)->create([
        'name' => 'Matias Cornelisen',
        'email' => 'matias.cornelisen@gmail.com',
    ]);

    $this->tor->update(['login_user_id' => $this->torUser->id]);
    $this->maty->update(['login_user_id' => $this->matyUser->id]);
});

it('limits a student login to that student record', function () {
    actingAs($this->torUser);

    $component = Livewire::test(CourseIndex::class)
        ->assertOk()
        ->assertSet('selectedStudentId', $this->tor->id);

    $students = $component->get('students');

    expect($students)->toHaveCount(1);
    expect($students->first()->id)->toBe($this->tor->id);
    expect($component->get('canSwitchStudents'))->toBeFalse();
});

it('lets evaluators view one student without editing the syllabus', function () {
    $evaluator = User::factory()->evaluator()->inHousehold($this->owner->household)->create([
        'name' => 'Independent Evaluator',
        'email' => 'evaluator@example.com',
    ]);

    StudentAccessGrant::create([
        'household_id' => $this->owner->household_id,
        'student_id' => $this->tor->id,
        'user_id' => $evaluator->id,
        'created_by_user_id' => $this->owner->id,
    ]);

    $course = $this->tor->courses()->where('title', 'World History')->firstOrFail();

    actingAs($evaluator);

    $index = Livewire::test(CourseIndex::class)
        ->assertOk()
        ->assertSet('selectedStudentId', $this->tor->id);

    $students = $index->get('students');

    expect($students)->toHaveCount(1);
    expect($students->first()->id)->toBe($this->tor->id);
    expect($index->get('canSwitchStudents'))->toBeFalse();

    Livewire::test(CourseHub::class, ['course' => $course])
        ->assertOk()
        ->assertSee('World History')
        ->assertSee('Week-by-week outline')
        ->set('newWeekTitle', 'Evaluator-added week')
        ->set('newWeekFocus', 'This should not be allowed.')
        ->call('addWeek')
        ->assertForbidden();

    assertDatabaseMissing('course_weeks', [
        'course_id' => $course->id,
        'title' => 'Evaluator-added week',
    ]);
});

it('does not let evaluators access another student in the household', function () {
    $evaluator = User::factory()->evaluator()->inHousehold($this->owner->household)->create();

    StudentAccessGrant::create([
        'household_id' => $this->owner->household_id,
        'student_id' => $this->tor->id,
        'user_id' => $evaluator->id,
        'created_by_user_id' => $this->owner->id,
    ]);

    $matyCourse = $this->maty->courses()->firstOrFail();

    actingAs($evaluator);

    Livewire::test(CourseHub::class, ['course' => $matyCourse])
        ->assertForbidden();
});

it('keeps evaluator assignment access read-only', function () {
    $evaluator = User::factory()->evaluator()->inHousehold($this->owner->household)->create();

    StudentAccessGrant::create([
        'household_id' => $this->owner->household_id,
        'student_id' => $this->tor->id,
        'user_id' => $evaluator->id,
        'created_by_user_id' => $this->owner->id,
    ]);

    $assignment = $this->tor->assignments()->where('status', 'assigned')->firstOrFail();

    actingAs($evaluator);

    Livewire::test(AssignmentShow::class, ['assignment' => $assignment])
        ->assertOk()
        ->assertSet('canEditAssignment', false)
        ->assertSet('canMoveAssignment', false)
        ->assertSet('canSubmitEvidence', false)
        ->assertSet('canSubmitReflection', false)
        ->set('evidence', 'Evaluator should not write evidence.')
        ->call('save')
        ->assertForbidden();

    Livewire::test(AssignmentShow::class, ['assignment' => $assignment])
        ->call('markDone')
        ->assertForbidden();
});

it('keeps evaluator course records read-only', function () {
    $evaluator = User::factory()->evaluator()->inHousehold($this->owner->household)->create();

    StudentAccessGrant::create([
        'household_id' => $this->owner->household_id,
        'student_id' => $this->tor->id,
        'user_id' => $evaluator->id,
        'created_by_user_id' => $this->owner->id,
    ]);

    $course = $this->tor->courses()->where('title', 'World History')->firstOrFail();

    actingAs($evaluator);

    Livewire::test(CourseHub::class, ['course' => $course])
        ->set('newResourceTitle', 'Evaluator Resource')
        ->call('addResource')
        ->assertForbidden();

    Livewire::test(CourseHub::class, ['course' => $course])
        ->set('newReadingTitle', 'Evaluator Reading')
        ->call('addReading')
        ->assertForbidden();

    Livewire::test(CourseHub::class, ['course' => $course])
        ->set('newLogTitle', 'Evaluator Log')
        ->call('addLog')
        ->assertForbidden();

    Livewire::test(CourseHub::class, ['course' => $course])
        ->set('finalGrade', 'A')
        ->call('saveGrading')
        ->assertForbidden();
});
