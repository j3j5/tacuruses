<?php

use App\Models\ActivityPub\Actor;
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
            $table->timestamps();
            $table->string('activityId');
            $table->string('type');
            $table->foreignIdFor(Actor::class)->constrained();
            // target_id is not constrained because it can point to actors or notes
            // depending on the type
            $table->foreignId('target_id');
            $table->string('object_type')->nullable();
            $table->json('object');
            $table->boolean('accepted')->default(false);

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
