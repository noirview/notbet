<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('bets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')
                ->constrained()->cascadeOnDelete();

            $table->integer('type');

            $table->integer('number_team')->nullable();
            $table->integer('number_period')->nullable();
            $table->integer('sign')->nullable();
            $table->decimal('value')->nullable();
            $table->decimal('coefficient', 5, 3);

            $table->integer('bookmaker');

            $table->timestamps();


        });
    }

    public function down()
    {
        Schema::dropIfExists('bets');
    }
};
