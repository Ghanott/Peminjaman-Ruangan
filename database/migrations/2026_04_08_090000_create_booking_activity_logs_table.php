<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_key', 80);
            $table->string('action_label', 80)->nullable();
            $table->string('old_status', 40)->nullable();
            $table->string('new_status', 40)->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('acted_at')->useCurrent();
            $table->timestamps();

            $table->index(['booking_id', 'acted_at']);
            $table->index(['booking_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_activity_logs');
    }
};
