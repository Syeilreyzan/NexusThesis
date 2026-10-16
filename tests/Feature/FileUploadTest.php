<?php

use App\Models\Thesis;
use App\Models\ThesisDocument;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('students can upload a supported thesis document', function () {
    Storage::fake('local');

    $student = User::factory()->create()->assignRole('student');
    $file = UploadedFile::fake()->create('research-thesis.pdf', 100, 'application/pdf');

    Livewire::actingAs($student)
        ->test('student.thesis-table')
        ->set('title', 'Research Thesis')
        ->set('abstract', 'A thesis abstract for upload testing.')
        ->set('thesisFile', $file)
        ->call('saveThesis')
        ->assertHasNoErrors();

    $thesis = Thesis::query()->sole();
    $document = ThesisDocument::query()->sole();

    expect($thesis->student_id)->toBe($student->id)
        ->and($document->thesis_id)->toBe($thesis->id)
        ->and($document->uploaded_by)->toBe($student->id)
        ->and($document->original_name)->toBe('research-thesis.pdf')
        ->and($document->document_type)->toBe('thesis')
        ->and($document->file_size)->toBe($file->getSize())
        ->and($document->file_hash)->toBe(hash_file('sha256', $file->getRealPath()));

    Storage::disk('local')->assertExists($document->storage_path);
});

test('students cannot upload unsupported thesis document types', function () {
    Storage::fake('local');

    $student = User::factory()->create()->assignRole('student');
    $file = UploadedFile::fake()->create('malicious.php', 10, 'application/x-php');

    Livewire::actingAs($student)
        ->test('student.thesis-table')
        ->set('title', 'Research Thesis')
        ->set('abstract', 'A thesis abstract for upload testing.')
        ->set('thesisFile', $file)
        ->call('saveThesis')
        ->assertHasErrors(['thesisFile']);

    expect(Thesis::query()->count())->toBe(0)
        ->and(ThesisDocument::query()->count())->toBe(0);

    Storage::disk('local')->assertDirectoryEmpty('thesis-documents');
});
