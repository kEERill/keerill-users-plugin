<?php namespace KEERill\Users\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class UpdateLogsTable extends Migration
{
    public function up()
    {
        Schema::table('oc_users_logs', function(Blueprint $table) {
            $table->text('data')->nullable();
        });
    }

    public function down()
    {
        Schema::table('oc_users_logs', function($table)
        {
            $table->dropColumn('data');
        });
    }
}
