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
        Schema::create('thesis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete()->nullable();
            $table->foreignId('examiner_id')->constrained('users')->cascadeOnDelete()->nullable();
            $table->string('title');
            $table->text('abstract');
            $table->enum('status', ['draft', 'proposal', 'submitted', 'sv_approved', 'candidature_approved', 'thesis_uploaded', 'sv_endorsed', 'examined', 'final_approved', 'revision_needed'])->default('draft');
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
