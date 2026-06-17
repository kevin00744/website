<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 舊角色對應到新的階層角色，避免既有資料違反新的 check constraint
        DB::table('users')->where('role', 'author')->update(['role' => 'staff']);
        DB::table('users')->where('role', 'viewer')->update(['role' => 'staff']);

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'editor', 'manager', 'staff'))");
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'staff'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'editor', 'author', 'viewer'))");
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'author'");
    }
};
