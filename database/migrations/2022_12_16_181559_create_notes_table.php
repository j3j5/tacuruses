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
            // https://www.w3.org/TR/activitystreams-vocabulary/#dfn-published
            $table->timestamp('published_at')->nullable();
            // https://www.w3.org/TR/activitystreams-vocabulary/#dfn-content
            $table->text('content');
            $table->json('contentMap')->nullable();
            // https://www.w3.org/TR/activitystreams-vocabulary/#dfn-summary
            $table->text('summary')->nullable()->comment("On Mastodon, this field contains the visible way when sensitive is true");
            $table->json('summaryMap')->nullable();
            // Mastodon-specific
            $table->boolean('sensitive')->default(false)->comment('Mastodon-specific; content warning');
            $table->json('to')->comment('array of recipients');
            // https://www.w3.org/TR/activitystreams-vocabulary/#dfn-bto
            $table->json('bto')->comment('array of recipients of the blind carbon copy');
            $table->json('cc')->nullable()->comment('array of recipients of the carbon copy');
            // https://www.w3.org/TR/activitystreams-vocabulary/#dfn-bcc
            $table->json('bcc')->nullable()->comment('array of recipients of the blind carbon copy');
            $table->string('inReplyTo')->nullable()->comment('activityId of the note is replying to, if any');
            // https://www.w3.org/TR/activitystreams-vocabulary/#dfn-generator
            $table->json('generator')->nullable()->comment('the entity that generated the object');
            // https://www.w3.org/TR/activitystreams-vocabulary/#dfn-location
            // https://www.w3.org/TR/activitystreams-vocabulary/#places
            $table->json('location')->nullable()->comment('');
            // https://www.w3.org/TR/activitystreams-vocabulary/#dfn-starttime
            $table->timestamp('startTime')->nullable();
            // https://www.w3.org/TR/activitystreams-vocabulary/#dfn-endtime
            $table->timestamp('endTime')->nullable();
            $table->json('attachments')->nullable();
            $table->json('tags')->nullable();
            $table->json('repliesRaw')->nullable(); // remote only
            $table->json('source')->nullable()->comment('original representation of the content');

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
