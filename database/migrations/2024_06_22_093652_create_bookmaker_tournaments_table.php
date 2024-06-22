<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('bookmaker_tournaments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('external_id');
            $table->foreignUuid('tournament_id')
                ->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->integer('bookmaker');

            $table->timestamps();

            $table->unique(['external_id', 'bookmaker']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookmaker_tournaments');
    }
};
