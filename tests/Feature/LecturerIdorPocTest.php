<?php

use App\Models\Thesis;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * --- Supervisor view: lecturer.supervisor-table ---
 *
 * selectedThesisId is a public, unlocked property. selectedThesis() and
 * previewThesis() both load the thesis with no check that
 * supervisor_id === auth()->id(). The preview modal for this component
 * doesn't actually render student name/email anywhere in the Blade
 * (unlike the examiner view below), so the concrete, provable leak here is
 * narrower: previewDocumentUrl is a plain public property, which Livewire
 * syncs back to the client regardless of what the template renders, and it
 * gets set to a URL built from the unauthorized thesis's slug — confirming
 * a document exists for a thesis this lecturer does not supervise, and
 * exposing its slug.
 */
test('a lecturer can fetch the document URL for a thesis they do not supervise', function () {
    $realSupervisor = User::factory()->create()->assignRole('lecturer');
    $attackerLecturer = User::factory()->create()->assignRole('lecturer');

    $thesis = Thesis::factory()->create([
        'supervisor_id' => $realSupervisor->id,  // NOT the attacker
        'status' => 'pending_approval',
    ]);

    \App\Models\ThesisDocument::create([
        'thesis_id' => $thesis->id,
        'uploaded_by' => $thesis->student_id,
        'document_type' => 'thesis',
        'original_name' => 'thesis.pdf',
        'storage_path' => 'thesis-documents/test.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'file_hash' => hash('sha256', 'test'),
    ]);

    Livewire::actingAs($attackerLecturer)
        ->test('lecturer.supervisor-table')
        ->call('previewThesis', $thesis->id)      // attacker is not this thesis's supervisor
        ->assertSet('selectedThesisId', $thesis->id)
        ->assertSet('previewDocumentUrl', fn ($url) => str_contains($url, $thesis->slug));
});

/**
 * Confirms the write path IS correctly guarded, unlike the read path above.
 * updateStatus() explicitly checks supervisor_id and aborts with 403.
 */
test('a lecturer cannot approve a thesis they do not supervise', function () {
    $realSupervisor = User::factory()->create()->assignRole('lecturer');
    $attackerLecturer = User::factory()->create()->assignRole('lecturer');

    $thesis = Thesis::factory()->create([
        'supervisor_id' => $realSupervisor->id,
        'status' => 'pending_approval',
    ]);

    Livewire::actingAs($attackerLecturer)
        ->test('lecturer.supervisor-table')
        ->call('previewThesis', $thesis->id)   // sets selectedThesisId as a side effect
        ->call('updateStatus', 'approved')
        ->assertStatus(403);

    $this->assertDatabaseHas('theses', [
        'id' => $thesis->id,
        'status' => 'pending_approval',  // unchanged
    ]);
});

/**
 * --- Examiner view: lecturer.examiner-table ---
 *
 * Same pattern as above, but worse: the preview modal actually renders
 * $this->selectedThesis->grade, so this leaks a real evaluation outcome
 * (or the fact that grading is still pending) for a thesis this lecturer
 * was never assigned to examine.
 */
test('a lecturer can see the grade of a thesis they do not examine', function () {
    $victimStudent = User::factory()->create()->assignRole('student');
    $realExaminer = User::factory()->create()->assignRole('lecturer');
    $attackerLecturer = User::factory()->create()->assignRole('lecturer');

    $gradedThesis = Thesis::factory()->create([
        'student_id' => $victimStudent->id,
        'examiner_id' => $realExaminer->id,     // NOT the attacker
        'grade' => '92% (A)',
        'grade_status' => 'completed',
        'status' => 'completed',
    ]);

    Livewire::actingAs($attackerLecturer)
        ->test('lecturer.examiner-table')
        ->call('previewThesis', $gradedThesis->id)
        ->assertSet('selectedThesisId', $gradedThesis->id)
        ->assertSee('92% (A)');   // the leaked grade, rendered in the "Graded: ..." badge
});

/**
 * Confirms the write path is correctly guarded here too.
 * submitGrade() explicitly checks examiner_id and aborts with 403.
 */
test('a lecturer cannot grade a thesis they do not examine', function () {
    $realExaminer = User::factory()->create()->assignRole('lecturer');
    $attackerLecturer = User::factory()->create()->assignRole('lecturer');

    $thesis = Thesis::factory()->create([
        'examiner_id' => $realExaminer->id,
    ]);

    Livewire::actingAs($attackerLecturer)
        ->test('lecturer.examiner-table')
        ->call('previewThesis', $thesis->id)
        ->set('gradeScore', 70)
        ->call('submitGrade')
        ->assertStatus(403);

    $this->assertDatabaseHas('theses', [
        'id' => $thesis->id,
        'grade' => null,
    ]);
});
