<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table_name = config('composer_test.table_name');

        Schema::create($table_name, function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->mediumText('message');
            $table->mediumText('return');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table_name = config('composer_test.table_name');

        Schema::drop($table_name);
    }
}
