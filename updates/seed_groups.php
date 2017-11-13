<?php namespace KEERill\Users\Updates;

use KEERill\Users\Models\Group;
use KEERill\Users\Models\Permission;
use October\Rain\Database\Updates\Seeder;

class SeedGroups extends Seeder
{
    public function run()
    {
        Group::create([
            'name' => 'Unactivated',
            'code' => 'no_active'
        ]);

        Group::create([
            'name' => 'Guest',
            'code' => 'guest'
        ]);

        Group::create([
            'name' => 'User',
            'code' => 'user'
        ]);

        Group::create([
            'name' => 'Banned',
            'code' => 'banned'
        ]);
    }
}