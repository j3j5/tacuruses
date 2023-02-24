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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('actor_id');
            $table->boolean('sensitive')->default(false);
            $table->text('text');
            $table->text('summary')->nullable();
            $table->string('inReplyTo')->nullable()->comment('activityId of the status is replying to');
            $table->string('language');
            $table->json('attachments')->nullable();
            $table->json('tags')->nullable();

            $table->foreign('actor_id')->references('id')->on('actors');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notes');
    }
};
