<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('bookmaker_events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('external_id');
            $table->foreignUuid('event_id')
                ->constrained()->cascadeOnDelete();

            $table->dateTime('start_at');
            $table->string('bookmaker');

            $table->timestamps();

            $table->unique(['external_id', 'bookmaker']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookmaker_events');
    }
};
