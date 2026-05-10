<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\User;
use App\Support\StarterPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $household = Household::firstOrCreate([
            'name' => config('homeschool.starter_household_name'),
        ]);

        $owner = null;

        foreach (config('homeschool.family_accounts', []) as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'household_id' => $household->id,
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => Hash::make(config('homeschool.default_password')),
                    'email_verified_at' => now(),
                ],
            );

            if ($account['role'] === 'owner') {
                $owner = $user;
            }
        }

        $owner ??= User::where('household_id', $household->id)->firstOrFail();

        app(StarterPlan::class)->ensureFor($owner);

        foreach (config('homeschool.family_accounts', []) as $account) {
            if (! isset($account['student_name'])) {
                continue;
            }

            $studentUser = User::where('email', $account['email'])->first();
            $student = $household->students()->where('name', $account['student_name'])->first();

            if ($studentUser && $student) {
                $student->forceFill(['login_user_id' => $studentUser->id])->save();
            }
        }
    }
}
