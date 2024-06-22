<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('surebet_bet', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('surebet_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignUuid('bet_id')
                ->constrained()->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('surebet_bet');
    }
};
