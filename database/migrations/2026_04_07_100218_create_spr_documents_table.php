<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spr_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->string('spr_number', 100)->unique();
            $table->date('issued_date');
            $table->foreignId('signed_by')->constrained('users')->restrictOnDelete();
            $table->text('signature_note')->nullable();
            $table->string('pdf_path');
            $table->timestamps();

            $table->index('issued_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spr_documents');
    }
};
