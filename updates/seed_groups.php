<?php namespace KEERill\Users\Updates;

use KEERill\Users\Models\Group;
use KEERill\Users\Models\Permission;
use October\Rain\Database\Updates\Seeder;

class SeedGroups extends Seeder
{
    public function run()
    {
        Group::create([
            'name' => 'Неактивированный',
            'code' => 'no_active'
        ]);

        Group::create([
            'name' => 'Гость',
            'code' => 'guest'
        ]);

        Group::create([
            'name' => 'Пользователь',
            'code' => 'user'
        ]);

        Group::create([
            'name' => 'Заблокированный',
            'code' => 'banned'
        ]);
    }
}