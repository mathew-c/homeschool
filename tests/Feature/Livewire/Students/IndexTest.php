<?php

use App\Livewire\Students\Index;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->parent = User::factory()->parent()->create();

    actingAs($this->parent);

    $this->tor = Student::factory()->ownedBy($this->parent)->create([
        'name' => 'Tor Cornelisen',
        'age' => 15,
        'level' => '9th grade',
        'position' => 0,
    ]);

    $this->maty = Student::factory()->ownedBy($this->parent)->create([
        'name' => 'Maty Cornelisen',
        'age' => 12,
        'level' => '6th grade',
        'position' => 1,
    ]);
});

it('renders student profile cards for parents', function () {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertViewIs('livewire.students.index')
        ->assertSee('Tor Cornelisen')
        ->assertSee('Maty Cornelisen')
        ->assertSee('Add Student');
});

it('creates a student school-file profile', function () {
    Carbon::setTestNow('2026-05-10 12:00:00');

    Livewire::test(Index::class)
        ->set('name', 'New Learner')
        ->set('birthDate', '2013-03-20')
        ->set('level', '7th grade')
        ->set('targetGradYear', '2031')
        ->set('weeklyCapacityHours', 24)
        ->set('bio', 'Curious, visual learner with a strong independent project streak.')
        ->set('learningStyle', 'Works best from written checklists and map-first planning.')
        ->set('strengths', 'Research, synthesis, and oral explanation.')
        ->set('friction', 'Open-ended assignments need clear finish lines.')
        ->set('collegeDirection', 'Engineering and applied AI are early themes.')
        ->set('schoolFileNotes', 'Preserve evaluator-ready context on project depth.')
        ->set('interestsText', "AI\nRobotics, World History")
        ->call('save')
        ->assertHasNoErrors();

    $student = Student::where('name', 'New Learner')->firstOrFail();

    expect($student->household_id)->toBe($this->parent->household_id);
    expect($student->age)->toBe(13);
    expect($student->interests)->toBe(['AI', 'Robotics', 'World History']);

    assertDatabaseHas('students', [
        'id' => $student->id,
        'level' => '7th grade',
        'target_grad_year' => '2031',
        'school_file_notes' => 'Preserve evaluator-ready context on project depth.',
        'position' => 2,
    ]);

    Carbon::setTestNow();
});

it('updates a student profile from the modal form', function () {
    Livewire::test(Index::class)
        ->call('edit', $this->tor)
        ->assertSet('name', 'Tor Cornelisen')
        ->set('name', 'Tor C.')
        ->set('bio', 'Updated front-page school-file summary.')
        ->set('schoolFileNotes', 'Updated evaluator-ready notes.')
        ->set('interestsText', 'History, Debate')
        ->call('save')
        ->assertHasNoErrors();

    $this->tor->refresh();

    expect($this->tor->name)->toBe('Tor C.');
    expect($this->tor->bio)->toBe('Updated front-page school-file summary.');
    expect($this->tor->school_file_notes)->toBe('Updated evaluator-ready notes.');
    expect($this->tor->interests)->toBe(['History', 'Debate']);
});

it('persists the parent-selected student card order', function () {
    Livewire::test(Index::class)
        ->call('sortStudent', $this->maty->id, 0)
        ->assertOk();

    expect($this->maty->refresh()->position)->toBe(0);
    expect($this->tor->refresh()->position)->toBe(1);
});

it('blocks student accounts from managing student profiles', function () {
    $studentUser = User::factory()->student()->inHousehold($this->parent->household)->create();

    actingAs($studentUser);

    get(route('students.index'))->assertForbidden();
});
