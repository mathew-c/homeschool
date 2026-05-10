<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::table('assignments')
            ->whereIn('status', ['backlog', 'today'])
            ->update(['status' => 'assigned']);

        DB::table('assignments')
            ->where('status', 'doing')
            ->update(['status' => 'in_progress']);
    }

    public function down(): void
    {
        DB::table('assignments')
            ->where('status', 'assigned')
            ->update(['status' => 'today']);

        DB::table('assignments')
            ->where('status', 'in_progress')
            ->update(['status' => 'doing']);
    }
};
