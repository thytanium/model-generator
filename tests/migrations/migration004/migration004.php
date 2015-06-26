<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Migration004 extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("users", function(Blueprint $table)
        {
            $table->increments("id");
            $table->string("name", 64);
            $table->string("email")
                ->unique();
            $table->integer('age')
                ->unsigned();
            $table->date('birth')
                ->nullable();
            $table->double('money');
            $table->enum('color', ['blue', 'red', 'white']);
            $table->rememberToken();
            $table->timestamps();

            $table->engine = "InnoDB";
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop("users");
    }

}
