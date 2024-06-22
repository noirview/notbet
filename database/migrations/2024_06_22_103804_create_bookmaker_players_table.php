<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('bookmaker_players', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('player_id')
                ->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->boolean('is_short_name');

            $table->integer('bookmaker');

            $table->timestamps();

            $table->unique(['name', 'is_short_name', 'bookmaker']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookmaker_players');
    }
};
