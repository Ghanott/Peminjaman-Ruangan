<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();

            $table->string('event_name');
            $table->text('event_description')->nullable();
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('participant_count')->nullable();

            $table->string('status', 40)->default('draft');
            $table->unsignedTinyInteger('current_step_order')->nullable();
            $table->string('current_role_key', 50)->nullable();

            $table->unsignedTinyInteger('revision_count')->default(0);
            $table->text('last_revision_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();

            $table->index(['room_id', 'event_date', 'start_time', 'end_time'], 'bookings_room_schedule_idx');
            $table->index('status');
            $table->index('current_role_key');
            $table->index(['organization_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
