<?php

use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\Actor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {

            $table->id();
            $table->timestamps();
            $table->timestamp('read_at')->nullable();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');

            $table->unsignedBigInteger('from_actor_id')
                ->nullable()
                ->comment('The actor "generating" the notification');
            $table->unsignedBigInteger('activity_id')
                ->nullable()
                ->comment('The activity that generated the notification');
            $table->foreign('from_actor_id')->references('id')->on('actors')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('activity_id')->references('id')->on('activities')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
