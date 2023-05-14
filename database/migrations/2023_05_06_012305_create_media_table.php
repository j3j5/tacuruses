<?php

use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\Note;
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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Note::class)
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignIdFor(Actor::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->text('description')->default('')->comment('Alt text for the file');
            $table->string('filename')->nullable()->comment('local filename');
            $table->string('content_type')->nullable()->comment('mime type');
            $table->string('filesize')->nullable();
            $table->timestamp('file_updated_at')->nullable()->comment('last datetime the file was updated');
            $table->string('remote_url')->nullable();
            $table->json('meta')->nullable();
            $table->string('hash')->nullable();
            $table->boolean('processed')->default(false);
            $table->string('thumb_filename')->nullable()->comment('local filename');
            $table->string('thumb_content_type')->nullable()->comment('mime type');
            $table->string('thumb_filesize')->nullable();
            $table->timestamp('thumb_updated_at')->nullable()->comment('last datetime the file was updated');
            $table->string('thumb_remote_url')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media');
    }
};
