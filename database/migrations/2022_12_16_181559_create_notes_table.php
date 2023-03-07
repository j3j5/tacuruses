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
            $table->text('content');
            $table->text('summary')->nullable();
            $table->json('summaryMap')->nullable();
            $table->string('inReplyTo')->nullable()->comment('activityId of the status is replying to');
            $table->json('to');
            $table->json('cc');
            $table->json('contentMap')->nullable();
            $table->string('generator')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('startTime')->nullable();
            $table->timestamp('endTime')->nullable();
            $table->json('attachments')->nullable();
            $table->json('tags')->nullable();
            $table->json('repliesRaw')->nullable(); // remote only

            $table->foreign('actor_id')->references('id')->on('actors');

            $table->string('type');
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
