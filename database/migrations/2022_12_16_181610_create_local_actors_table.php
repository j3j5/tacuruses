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
        Schema::connection('mysql')->create('local_actors', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            // Local Project
            $table->string('model');
            // ActivityPub
            $table->string('name');
            $table->string('username');
            $table->text('avatar')->nullable();
            $table->text('header')->nullable();
            $table->text('bio')->nullable();
            $table->json('alsoKnownAs')->nullable();
            $table->json('properties')->nullable();

            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql')->dropIfExists('local_actors');
    }
};
