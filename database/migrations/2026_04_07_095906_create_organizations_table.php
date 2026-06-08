<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();
            $table->string('name');
            $table->enum('type', ['ormawa', 'ukm']);
            $table->foreignId('ketua_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kaprodi_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pembina_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
