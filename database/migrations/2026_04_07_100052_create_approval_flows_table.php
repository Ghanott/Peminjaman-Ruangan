<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->enum('organization_type', ['ormawa', 'ukm']);
            $table->unsignedTinyInteger('step_order');
            $table->string('role_key', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_type', 'step_order']);
            $table->index(['role_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flows');
    }
};
