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
        Schema::connection('mysql')->create('remote_actors', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('activityId');
            $table->string('type');
            $table->string('username');
            $table->string('name');
            $table->text('bio')->nullable();
            $table->text('url');
            $table->text('avatar')->nullable();
            $table->text('header')->nullable();

            $table->string('inbox');
            $table->text('sharedInbox');

            $table->string('publicKeyId');
            $table->text('publicKey');

            $table->index('activityId');
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
        Schema::connection('mysql')->dropIfExists('remote_actors');
    }
};
