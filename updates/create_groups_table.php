<?php namespace KEERill\Users\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('oc_users_groups', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('code');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('oc_users_groups');
    }
}
