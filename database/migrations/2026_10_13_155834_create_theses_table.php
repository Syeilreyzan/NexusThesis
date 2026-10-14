<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('theses', function (Blueprint $table) {
            $table->id(); // bigint, PK[cite: 1]

            // Foreign Keys[cite: 1]
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('examiner_id')->nullable()->constrained('users')->nullOnDelete();

            // Content[cite: 1]
            $table->string('title');
            $table->text('abstract')->nullable(); // Assuming abstract might be nullable initially, adjust if strictly required

            // Status Enum[cite: 1]
            $table->string('status')->default('draft');

            // Timestamps[cite: 1]
            $table->timestamp('examiner_access_expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theses');
    }
};
