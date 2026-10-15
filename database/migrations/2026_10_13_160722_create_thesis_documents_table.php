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
        Schema::create('thesis_documents', function (Blueprint $table) {
            $table->id(); // bigint, PK[cite: 2]

            // Foreign Keys
            $table->foreignId('thesis_id')->constrained('theses')->cascadeOnDelete(); // References theses.id[cite: 2]
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete(); // References users.id[cite: 2]

            // Document Details
            $table->string('document_type'); // Used string instead of enum for flexibility (proposal, progress_report, final_thesis)[cite: 2]
            $table->string('original_name'); // e.g., my_thesis.pdf[cite: 2]
            $table->string('storage_path'); // UUID path outside public root[cite: 2]
            $table->string('mime_type'); // Strictly application/pdf[cite: 2]
            $table->integer('file_size'); // In bytes (Max 10MB)[cite: 2]
            $table->string('file_hash'); // SHA-256 for integrity[cite: 2]

            // Mock API Result
            $table->integer('similarity_score')->nullable(); // Mocked API result, made nullable as it might be updated later[cite: 2]

            $table->timestamps(); // created_at_updated_at[cite: 2]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thesis_documents');
    }
};
