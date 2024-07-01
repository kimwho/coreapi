<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedByToMilestonesTable extends Migration
{
    public function up()
    {
        Schema::table('milestone', function (Blueprint $table) {
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
        });
    }

    public function down()
    {
        Schema::table('milestone', function (Blueprint $table) {
            $table->dropColumn('deleted_by');
        });
    }
}
