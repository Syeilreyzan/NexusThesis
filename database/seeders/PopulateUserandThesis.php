<?php

namespace Database\Seeders;

use App\Models\Thesis;
use App\Models\ThesisDocument;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PopulateUserandThesis extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Fetch existing roles
        $studentRole = Role::findByName('student');
        $lecturerRole = Role::findByName('lecturer');

        // 1. Create Generic Lecturer Users
        $lecturer1 = User::firstOrCreate(
            ['email' => 'lecturer1@example.com'],
            [
                'name' => 'Lecturer One',
                'password' => Hash::make('password'),
            ]
        );
        $lecturer1->assignRole($lecturerRole);

        $lecturer2 = User::firstOrCreate(
            ['email' => 'lecturer2@example.com'],
            [
                'name' => 'Lecturer Two',
                'password' => Hash::make('password'),
            ]
        );
        $lecturer2->assignRole($lecturerRole);

        // 2. Create Generic Student Users
        $student1 = User::firstOrCreate(
            ['email' => 'student1@example.com'],
            [
                'name' => 'Student One',
                'password' => Hash::make('password'),
            ]
        );
        $student1->assignRole($studentRole);

        $student2 = User::firstOrCreate(
            ['email' => 'student2@example.com'],
            [
                'name' => 'Student Two',
                'password' => Hash::make('password'),
            ]
        );
        $student2->assignRole($studentRole);

        // 3. Create Sample Thesis Records
        $thesis1 = Thesis::firstOrCreate(
            ['title' => 'Development of Role-Based Access System'],
            [
                'student_id' => $student1->id,
                'supervisor_id' => $lecturer1->id,
                'examiner_id' => $lecturer2->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $thesis2 = Thesis::firstOrCreate(
            ['title' => 'Analysis of Automated CI/CD Pipelines'],
            [
                'student_id' => $student2->id,
                'supervisor_id' => $lecturer2->id,
                'examiner_id' => $lecturer1->id,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 4. Create Sample Thesis Documents
        ThesisDocument::firstOrCreate(
            [
                'thesis_id' => $thesis1->id,
                'original_name' => 'proposal_draft_v1.pdf',
            ],
            [
                'uploaded_by' => $student1->id,
                'document_type' => 'proposal',
                'storage_path' => 'theses/documents/proposal_draft_v1.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 2048000, // 2MB in bytes
                'file_hash' => hash('sha256', 'proposal_draft_v1'),
                'similarity_score' => 12.50,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        ThesisDocument::firstOrCreate(
            [
                'thesis_id' => $thesis2->id,
                'original_name' => 'final_thesis_submission.pdf',
            ],
            [
                'uploaded_by' => $student2->id,
                'document_type' => 'final_report',
                'storage_path' => 'theses/documents/final_thesis_submission.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 5120000, // 5MB in bytes
                'file_hash' => hash('sha256', 'final_thesis_submission'),
                'similarity_score' => 4.20,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
