<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Thesis;
use App\Models\ThesisDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure roles exist
        $roles = ['admin', 'lecturer', 'student'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $password = 'Password123!';

        // 2. Create 1 Admin
        User::updateOrCreate(
            ['email' => 'admin@nexusthesis.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make($password),
            ]
        )->syncRoles(['admin']);

        // 3. Create 20 Lecturers & store them in an array for easy referencing
        $lecturers = [];
        for ($i = 1; $i <= 20; $i++) {
            $lecturers[$i] = User::updateOrCreate(
                ['email' => sprintf('lecturer%02d@nexusthesis.com', $i)],
                [
                    'name' => sprintf('Lecturer %02d', $i),
                    'password' => Hash::make($password),
                ]
            );
            $lecturers[$i]->syncRoles(['lecturer']);
        }

        // 4. Create 10 Students & store them in an array
        $students = [];
        for ($i = 1; $i <= 10; $i++) {
            $students[$i] = User::updateOrCreate(
                ['email' => sprintf('student%02d@nexusthesis.com', $i)],
                [
                    'name' => sprintf('Student %02d', $i),
                    'password' => Hash::make($password),
                ]
            );
            $students[$i]->syncRoles(['student']);
        }

        // 5. Create Sample Thesis Records for the first 5 students
        $thesis1 = Thesis::firstOrCreate(
            ['title' => 'Development of Role-Based Access System'],
            [
                'student_id' => $students[1]->id,
                'supervisor_id' => $lecturers[1]->id,
                'examiner_id' => $lecturers[2]->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $thesis2 = Thesis::firstOrCreate(
            ['title' => 'Analysis of Automated CI/CD Pipelines'],
            [
                'student_id' => $students[2]->id,
                'supervisor_id' => $lecturers[2]->id,
                'examiner_id' => $lecturers[1]->id,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $thesis3 = Thesis::firstOrCreate(
            ['title' => 'Secure Local Network Routing via WireGuard'],
            [
                'student_id' => $students[3]->id,
                'supervisor_id' => $lecturers[3]->id,
                'examiner_id' => $lecturers[4]->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $thesis4 = Thesis::firstOrCreate(
            ['title' => 'Optimizing SQLite Performance in Web Applications'],
            [
                'student_id' => $students[4]->id,
                'supervisor_id' => $lecturers[4]->id,
                'examiner_id' => $lecturers[3]->id,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $thesis5 = Thesis::firstOrCreate(
            ['title' => 'Automated Document Plagiarism Detection Framework'],
            [
                'student_id' => $students[5]->id,
                'supervisor_id' => $lecturers[5]->id,
                'examiner_id' => $lecturers[1]->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 6. Create Sample Thesis Documents
        ThesisDocument::firstOrCreate(
            [
                'thesis_id' => $thesis1->id,
                'original_name' => 'proposal_draft_v1.pdf',
            ],
            [
                'uploaded_by' => $students[1]->id,
                'document_type' => 'proposal',
                'storage_path' => 'theses/documents/proposal_draft_v1.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 2048000,
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
                'uploaded_by' => $students[2]->id,
                'document_type' => 'final_report',
                'storage_path' => 'theses/documents/final_thesis_submission.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 5120000,
                'file_hash' => hash('sha256', 'final_thesis_submission'),
                'similarity_score' => 4.20,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
