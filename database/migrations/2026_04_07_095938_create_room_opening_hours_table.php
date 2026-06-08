<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('open_time');
            $table->time('close_time');
            $table->boolean('is_open')->default(true);
            $table->timestamps();

            $table->unique(['room_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_opening_hours');
    }
};
