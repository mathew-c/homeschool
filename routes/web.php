<?php

use App\Livewire\AssignmentShow;
use App\Livewire\CourseHub;
use App\Livewire\CourseIndex;
use App\Livewire\HomeschoolBoard;
use App\Livewire\Students\Index as StudentIndex;
use App\Livewire\User\Profile;
use App\Livewire\Users\Index;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('welcome');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', HomeschoolBoard::class)->name('dashboard');
    Route::get('/students', StudentIndex::class)
        ->middleware('can:create,'.Student::class)
        ->name('students.index');

    Route::get('/courses', CourseIndex::class)->name('courses.index');
    Route::get('/courses/{course}', CourseHub::class)->name('courses.show');
    Route::get('/assignments/{assignment}', AssignmentShow::class)->name('assignments.show');

    Route::get('/users', Index::class)
        ->middleware('can:viewAny,'.User::class)
        ->name('users.index');

    Route::get('/user/profile', Profile::class)->name('user.profile');
});

require __DIR__.'/auth.php';
