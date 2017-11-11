<?php namespace KEERill\Users\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateAccessLogsTable extends Migration
{
    public function up()
    {
        Schema::create('oc_users_access_logs', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id');
            $table->string('ip_address')->nullable();
            $table->boolean('is_success')->default(0);
            $table->string('message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('oc_users_access_logs');
    }
}
