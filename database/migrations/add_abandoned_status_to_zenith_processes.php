<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('zenith.table_names.processes', 'zenith_processes');
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` ENUM('idle', 'working', 'terminated', 'abandoned') NOT NULL DEFAULT 'idle'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TYPE status ADD VALUE IF NOT EXISTS 'abandoned'");
        }
        // SQLite does not enforce enum constraints — no change needed
    }
};
