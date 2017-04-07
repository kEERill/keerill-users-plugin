<?php namespace kEERill\Users\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreatePermissionsTable extends Migration
{
    public function up()
    {
        Schema::create('oc_users_permissions', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('oc_users_permissions');
    }
}
