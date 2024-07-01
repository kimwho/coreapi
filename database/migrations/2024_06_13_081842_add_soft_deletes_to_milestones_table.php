<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToMilestonesTable extends Migration
{
    public function up()
    {
        Schema::table('milestone', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('milestone', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
