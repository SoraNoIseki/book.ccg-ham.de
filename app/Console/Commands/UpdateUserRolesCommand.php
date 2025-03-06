<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;

class UpdateUserRolesCommand extends Command
{
    /**
     * 
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'book:update-user-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user roles.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roles = [
            ['internal_name' => 'admin', 'name' => '管理员'],
            ['internal_name' => 'library', 'name' => '图书馆'],
            ['internal_name' => 'calendar', 'name' => '年历制作'],
            ['internal_name' => 'songs_management', 'name' => '歌曲管理'],
            ['internal_name' => 'planer_admin', 'name' => '服事安排（管理员）'],
            ['internal_name' => 'visitor', 'name' => '访客'],
            ['internal_name' => 'planer_write_worship', 'name' => '服事安排（编辑：礼拜组）'],
            ['internal_name' => 'planer_write_education', 'name' => '服事安排（编辑：教育组）'],
            ['internal_name' => 'planer_write_book', 'name' => '服事安排（编辑：图书组）'],
            ['internal_name' => 'planer_write_service', 'name' => '服事安排（编辑：服务组）'],
            ['internal_name' => 'planer_write_management', 'name' => '服事安排（编辑：管堂组）'],
            ['internal_name' => 'planer_read', 'name' => '服事安排（查看）'],
        ];

        $migrations = [
            ['old' => 'planer', 'new' => 'planer_admin'],
            ['old' => 'planer_worship', 'new' => 'planer_write_worship'],
            ['old' => 'planer_education', 'new' => 'planer_write_education'],
            ['old' => 'planer_book', 'new' => 'planer_write_book'],
            ['old' => 'planer_service', 'new' => 'planer_write_service'],
            ['old' => 'planer_management', 'new' => 'planer_write_management'],
        ];

        foreach ($migrations as $migration) {
            Role::where('internal_name', $migration['old'])->update(['internal_name' => $migration['new']]);
        }

        foreach ($roles as $role) {
            Role::updateOrCreate(['internal_name' => $role['internal_name']], [
                'internal_name' => $role['internal_name'],
                'name' => $role['name'],
            ]);
        }


    }
}