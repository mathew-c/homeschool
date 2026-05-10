<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property string $name
 * @property string $email
 * @property string $password
 * @property Carbon $email_verified_at
 * @property string $remember_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'household_id',
        'name',
        'email',
        'role',
        'permissions',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'role' => UserRole::class,
            'permissions' => 'array',
            'password' => 'hashed',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class, 'login_user_id');
    }

    public function studentAccessGrants(): HasMany
    {
        return $this->hasMany(StudentAccessGrant::class)->active();
    }

    public function activityEvents(): HasMany
    {
        return $this->hasMany(ActivityEvent::class, 'actor_user_id');
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        $role = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;

        return collect($roles)
            ->map(fn (UserRole|string $role): string => $role instanceof UserRole ? $role->value : $role)
            ->contains($role);
    }

    public function permissionValues(): array
    {
        $role = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;
        $permissions = config("homeschool.role_permissions.{$role}", []);
        $overrides = $this->permissions ?? [];

        if (array_is_list($overrides)) {
            return array_values(array_unique([...$permissions, ...$overrides]));
        }

        foreach ($overrides as $permission => $allowed) {
            if ($allowed) {
                $permissions[] = $permission;

                continue;
            }

            $permissions = array_values(array_diff($permissions, [$permission]));
        }

        return array_values(array_unique($permissions));
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $permission = $permission instanceof Permission ? $permission->value : $permission;

        return in_array($permission, $this->permissionValues(), true);
    }

    public function visibleStudentsQuery(): Builder
    {
        if (! $this->household_id && ! $this->hasRole(UserRole::Student, UserRole::Evaluator)) {
            return Student::query()->whereRaw('1 = 0');
        }

        if ($this->hasRole(UserRole::Owner, UserRole::Parent)) {
            return Student::query()
                ->where('household_id', $this->household_id);
        }

        if ($this->hasRole(UserRole::Student)) {
            return Student::query()
                ->where('login_user_id', $this->id);
        }

        if ($this->hasRole(UserRole::Evaluator)) {
            return Student::query()
                ->whereIn('id', $this->studentAccessGrants()->select('student_id'));
        }

        return Student::query()->whereRaw('1 = 0');
    }

    public function canAccessStudent(Student $student): bool
    {
        if ($this->hasRole(UserRole::Owner, UserRole::Parent)) {
            return $this->household_id !== null
                && $student->household_id === $this->household_id
                && $this->hasPermission(Permission::ViewStudents);
        }

        if ($this->hasRole(UserRole::Student)) {
            return $student->login_user_id === $this->id
                && $this->hasPermission(Permission::ViewStudents);
        }

        if ($this->hasRole(UserRole::Evaluator)) {
            return $this->hasPermission(Permission::ViewStudents)
                && $this->studentAccessGrants()
                    ->where('student_id', $student->id)
                    ->exists();
        }

        return false;
    }

    public function canSwitchStudents(): bool
    {
        return $this->hasPermission(Permission::SwitchStudents)
            && $this->hasRole(UserRole::Owner, UserRole::Parent);
    }
}
