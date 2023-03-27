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
        Schema::create('actors', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // ActivityPub
            $table->string('name');
            $table->string('username');
            $table->text('avatar')->nullable();
            $table->text('header')->nullable();
            $table->text('bio')->nullable();
            $table->json('alsoKnownAs')->nullable();
            $table->json('properties')->nullable();
            $table->string('language')->default(config('app.locale'));

            // RemoteActor
            $table->string('activityId')->nullable();
            $table->string('type')->nullable();
            $table->text('url')->nullable();

            $table->string('followers_url')->nullable();
            $table->string('following_url')->nullable();
            $table->string('inbox')->nullable();
            $table->string('outbox')->nullable();
            $table->text('sharedInbox')->nullable();

            $table->string('publicKeyId')->nullable();
            $table->text('publicKey')->nullable();

            // Indexes
            $table->index('activityId');
            $table->index('publicKeyId');
            $table->index('username');

            // STI
            $table->string('actor_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actors');
    }
};
