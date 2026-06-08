<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->unsignedSmallInteger('round_no');
            $table->unsignedTinyInteger('requested_from_step');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'round_no']);
            $table->index(['booking_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_revisions');
    }
};
