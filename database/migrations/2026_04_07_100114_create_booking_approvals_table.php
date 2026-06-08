<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->string('role_key', 50);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', ['approve', 'reject', 'request_revision', 'resubmit']);
            $table->text('note')->nullable();
            $table->timestamp('acted_at')->useCurrent();
            $table->timestamps();

            $table->index(['booking_id', 'step_order']);
            $table->index(['booking_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_approvals');
    }
};
