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
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->string('email')->unique();
			$table->string('password', 60);
			$table->rememberToken();
			$table->timestamps();

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
