<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            ['name' => '管理员', 'internal_name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PPT制作', 'internal_name' => 'ppt', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '图书馆', 'internal_name' => 'library', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '年历制作', 'internal_name' => 'calendar', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '歌曲管理', 'internal_name' => 'songs_management', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '服事安排', 'internal_name' => 'planer', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '访客', 'internal_name' => 'visitor', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
