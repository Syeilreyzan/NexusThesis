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

test('assigned examiners can grade a thesis and create an audit log', function () {
    $examiner = User::factory()->create()->assignRole('lecturer');
    $thesis = Thesis::factory()->create([
        'examiner_id' => $examiner->id,
    ]);

    Livewire::actingAs($examiner)
        ->test('lecturer.examiner-table')
        ->set('selectedThesisId', $thesis->id)
        ->set('gradeScore', 85)
        ->assertSet('gradeStatus', 'A (Excellent)')
        ->call('submitGrade')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('theses', [
        'id' => $thesis->id,
        'grade' => '85% (A)',
        'grade_status' => 'completed',
        'status' => 'completed',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $examiner->id,
        'action' => 'grade_submitted',
    ]);
});

test('examiner grading rejects scores above 100', function () {
    $examiner = User::factory()->create()->assignRole('lecturer');
    $thesis = Thesis::factory()->create([
        'examiner_id' => $examiner->id,
    ]);

    Livewire::actingAs($examiner)
        ->test('lecturer.examiner-table')
        ->set('selectedThesisId', $thesis->id)
        ->set('gradeScore', 101)
        ->call('submitGrade')
        ->assertHasErrors(['gradeScore']);

    $this->assertDatabaseHas('theses', [
        'id' => $thesis->id,
        'grade' => null,
        'grade_status' => null,
    ]);
});
