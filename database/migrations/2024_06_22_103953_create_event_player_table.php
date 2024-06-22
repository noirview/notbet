<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('event_player', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('event_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignUuid('player_id')
                ->constrained()->cascadeOnDelete();

            $table->integer('team_number');
            $table->integer('position_number');

            $table->timestamps();

            $table->unique(['event_id', 'player_id', 'team_number', 'position_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_player');
    }
};
