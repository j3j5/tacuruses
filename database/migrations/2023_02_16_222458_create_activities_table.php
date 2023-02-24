<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('activityId');
            $table->string('type');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedBigInteger('target_id');
            $table->string('object_type')->nullable();
            $table->json('object');
            $table->timestamps();

            $table->unique('activityId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actions');
    }
};
