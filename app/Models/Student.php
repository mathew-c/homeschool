<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'user_id',
        'login_user_id',
        'name',
        'age',
        'birth_date',
        'level',
        'target_grad_year',
        'photo_path',
        'bio',
        'weekly_capacity_hours',
        'learning_style',
        'strengths',
        'friction',
        'college_direction',
        'school_file_notes',
        'position',
        'interests',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'birth_date' => 'date',
            'weekly_capacity_hours' => 'integer',
            'position' => 'integer',
            'interests' => 'array',
        ];
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        if (Str::startsWith($this->photo_path, ['http://', 'https://', '/'])) {
            return $this->photo_path;
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::substr($part, 0, 1))
            ->implode('');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function loginUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'login_user_id');
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(StudentAccessGrant::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
