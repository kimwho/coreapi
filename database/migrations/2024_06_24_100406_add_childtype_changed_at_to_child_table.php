<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('child', function (Blueprint $table) {
        $table->timestamp('childtype_changed_at')->nullable();
    });
}

public function down()
{
    Schema::table('child', function (Blueprint $table) {
        $table->dropColumn('childtype_changed_at');
    });
}

};
