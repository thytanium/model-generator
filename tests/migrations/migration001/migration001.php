<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Migration001 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('languages', function (Blueprint $t) {
            $t->tinyInteger('id')->unsigned();
            $t->string('language', 32);
            $t->string('short', 5);

            $t->primary('id');

            $t->engine = "InnoDB";
        });

		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->string('email')->unique();
			$table->string('password', 60);
            $table->integer('language_id')
                ->unsigned()
                ->nullable();
			$table->rememberToken();
			$table->timestamps();

            $table->foreign('language_id')
                ->references('id')
                ->on('languages');

			$table->engine = "InnoDB";
		});

        /**
         * User groups table
         */
        Schema::create('user_groups', function (Blueprint $t) {
            $t->increments('id');
            $t->string('group', 64);

            $t->engine = "InnoDB";
        });

        /**
         * Pivot table user - user_group
         */
        Schema::create('user_user_group', function (Blueprint $t) {
            $t->integer('id_user')->unsigned();
            $t->integer('id_user_group')->unsigned();

            $t->primary(['id_user', 'id_user_group']);

            $t->foreign('id_user')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $t->foreign('id_user_group')
                ->references('id')
                ->on('user_groups')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $t->engine = "InnoDB";
        });

        /**
         * Students table
         */
        Schema::create('students', function (Blueprint $t) {
            $t->increments('id');
            $t->string('student_name', 128);
            $t->string('major', 64);
            $t->date('birth');
            $t->integer('user_id')->unsigned();

            $t->foreign('user_id')
                ->references('id')
                ->on('users');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('user_user_group');
        Schema::drop('user_groups');
		Schema::drop('users');
	}

}
