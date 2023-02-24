<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('target_id'); // A status
            $table->text('activityId');

            $table->timestamps();

            $table->foreign('actor_id')->references('id')->on('actors');
            $table->foreign('target_id')->references('id')->on('notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql')->dropIfExists('shares');
    }
};
