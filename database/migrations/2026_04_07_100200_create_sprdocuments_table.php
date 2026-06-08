<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy migration kept for order compatibility.
        // Main table for SPR is `spr_documents` in the next migration.
    }

    public function down(): void
    {
        // no-op
    }
};
